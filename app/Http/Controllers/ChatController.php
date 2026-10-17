<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /** Live Messenger account customers are handed off to when the bot is not enough. */
    public const MESSENGER_URL = 'https://web.facebook.com/profile.php?id=100063831981795&rdid=J0BCP6eqTK2B8p6d&share_url=https%3A%2F%2Fweb.facebook.com%2Fshare%2F1Bqoxp5CiZ%2F%3F_rdc%3D1%26_rdr#';
    public const MESSENGER_NAME = "Mera's Merchandise";

    /** Number of customer messages after which the Messenger handoff is offered. */
    public const HANDOFF_AFTER_MESSAGES = 3;

    public static function getMessengerHandoff(?string $customText = null): array
    {
        return [
            'name' => self::MESSENGER_NAME,
            'url' => self::MESSENGER_URL,
            'label' => 'Chat live on Messenger',
            'text' => $customText ?? ("💬 Want to talk to a real person?\nYou have asked a few questions already, so " . self::MESSENGER_NAME . " can help you live on Messenger for anything I miss."),
        ];
    }

    /**
     * Count the customer's messages and decide whether it is time to offer a live
     * Messenger handoff. Offered once per session; the bot keeps answering after it.
     */
    private function resolveHandoff(Request $request): ?array
    {
        $sent = $request->session()->increment('chatbot_customer_messages');

        if ($sent < self::HANDOFF_AFTER_MESSAGES || $request->session()->get('chatbot_handoff_offered')) {
            return null;
        }

        $request->session()->put('chatbot_handoff_offered', true);

        return self::getMessengerHandoff();
    }

    public function index()
    {
        if (!auth()->check()) {
            $guestMessages = collect([
                (object) [
                    'user_name' => "Mera's Support Bot",
                    'message' => "Hello! 👋 Welcome to Mera's Store support assistant. How can I help you today? Ask me about products, store hours, location, or payment options!",
                    'created_at' => now(),
                ]
            ]);

            return view('chat.index', [
                'messages' => $guestMessages,
                'supportInquiries' => collect(),
                'messagesToday' => 0,
                'totalMessages' => 0,
                'pendingInquiries' => 0,
                'respondedToday' => 0,
            ]);
        }

        $user = auth()->user();
        $supportInquiries = Inquiry::with('respondent')->latest()->limit(8)->get();

        if ($user->role === 'user') {
            // Customer only sees their own messages, bot messages, and messages from staff/admin
            $staffNames = User::where('role', 'admin')->pluck('name')->toArray();
            $allowedNames = array_merge([$user->name, "Mera's Support Bot", "Mera's Support Assistant"], $staffNames);

            $messages = Message::whereIn('user_name', $allowedNames)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        } else {
            // Admins/Staff see all messages
            $messages = Message::orderByDesc('id')->limit(100)->get()->reverse()->values();
        }

        return view('chat.index', [
            'messages' => $messages,
            'supportInquiries' => $supportInquiries,
            'messagesToday' => Message::whereDate('created_at', today())->count(),
            'totalMessages' => Message::count(),
            'pendingInquiries' => Inquiry::where('status', 'pending')->count(),
            'respondedToday' => Inquiry::where('status', 'responded')->whereDate('responded_at', today())->count(),
        ]);
    }

    public function messages()
    {
        if (!auth()->check()) {
            return response()->json([]);
        }

        $user = auth()->user();

        if ($user->role === 'user') {
            $staffNames = User::where('role', 'admin')->pluck('name')->toArray();
            $allowedNames = array_merge([$user->name, "Mera's Support Bot", "Mera's Support Assistant"], $staffNames);

            $messages = Message::whereIn('user_name', $allowedNames)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        } else {
            $messages = Message::orderByDesc('id')->limit(100)->get()->reverse()->values();
        }

        return response()->json($messages->map(function (Message $message) {
            return [
                'user_name' => $message->user_name,
                'message' => $message->message,
                'created_at' => optional($message->created_at)->diffForHumans(),
            ];
        }));
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        $msg = Message::create([
            'user_name' => $user->name,
            'message' => $request->input('message'),
        ]);

        \App\Models\ActivityLog::log('chat_message', 'Sent a chat message: "' . \Illuminate\Support\Str::limit($msg->message, 50) . '"');

        // Automated Chatbot Response for Customers!
        if ($user->role === 'user') {
            $userMessage = $request->input('message');
            $botData = $this->getDetailedBotResponse($userMessage);
            $botReply = $botData['reply'];

            if ($botReply) {
                Message::create([
                    'user_name' => "Mera's Support Bot",
                    'message' => $botReply,
                ]);
            }

            $handoff = (!empty($botData['force_handoff'])) ? self::getMessengerHandoff() : $this->resolveHandoff($request);

            if ($handoff) {
                Message::create([
                    'user_name' => "Mera's Support Bot",
                    'message' => $handoff['text'] . "\n" . $handoff['url'],
                ]);
            }
        }

        return redirect()->route('chat.index')->with('notice', 'Message sent.');
    }

    /**
     * AJAX Endpoint for Chatbot Widget (Supports Guest & Authenticated Users)
     */
    public function botResponse(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $userMessage = trim($request->input('message'));
        $userName = auth()->check() ? auth()->user()->name : 'Guest Customer';

        // Log message if user is authenticated
        if (auth()->check()) {
            Message::create([
                'user_name' => $userName,
                'message' => $userMessage,
            ]);
            \App\Models\ActivityLog::log('chat_message', 'Sent chatbot message: "' . \Illuminate\Support\Str::limit($userMessage, 50) . '"');
        }

        $botData = $this->getDetailedBotResponse($userMessage);
        $handoff = (!empty($botData['force_handoff'])) ? self::getMessengerHandoff() : $this->resolveHandoff($request);

        // Save bot response to conversation if user is logged in
        if (auth()->check()) {
            Message::create([
                'user_name' => "Mera's Support Bot",
                'message' => $botData['reply'],
            ]);

            if ($handoff) {
                Message::create([
                    'user_name' => "Mera's Support Bot",
                    'message' => $handoff['text'] . "\n" . $handoff['url'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'user_name' => $userName,
            'reply' => $botData['reply'],
            'suggestions' => $botData['suggestions'],
            'products' => $botData['products'] ?? [],
            'handoff' => $handoff,
        ]);
    }

    /**
     * Generate an intelligent, helpful bot response based on customer keywords and store inventory.
     */
    private function getBotResponse($message)
    {
        $data = $this->getDetailedBotResponse($message);
        return $data['reply'];
    }

    /**
     * Generate structured bot response with reply, product matches, and suggested chips.
     */
    private function getDetailedBotResponse($message)
    {
        $lowerMsg = strtolower(trim($message));
        $defaultSuggestions = ["Store Hours 🕒", "Location 📍", "Check Products 🛍️", "Payment Methods 💳", "Messenger 💬"];

        // Live Messenger / Real person intent
        if (str_contains($lowerMsg, 'messenger') || str_contains($lowerMsg, 'chat live') || str_contains($lowerMsg, 'live chat')
            || str_contains($lowerMsg, 'human') || str_contains($lowerMsg, 'agent') || str_contains($lowerMsg, 'person')
            || str_contains($lowerMsg, 'staff') || str_contains($lowerMsg, 'talk to someone') || str_contains($lowerMsg, 'real person')
            || str_contains($lowerMsg, 'talk to person') || str_contains($lowerMsg, 'speak with') || str_contains($lowerMsg, 'facebook')
            || str_contains($lowerMsg, 'fb')) {
            return [
                'reply' => "💬 Want to chat with a real person?\nYou can talk to us live on Messenger for immediate personal assistance from our team!",
                'suggestions' => ["Store Hours 🕒", "Location 📍", "Check Products 🛍️"],
                'force_handoff' => true,
            ];
        }

        // Product inquiry signals are read up front so "Hi, do you have laptops?" is
        // answered as a product question instead of a plain greeting.
        $queryTokens = $this->extractQueryTokens($lowerMsg);

        // A message that is just an item name ("socks", "rubber shoes") is a product lookup
        $isItemLookup = !empty($queryTokens) && count(explode(' ', $this->normalizeSearchText($lowerMsg))) <= 4;

        $hasProductIntent = $isItemLookup || str_contains($lowerMsg, 'product') || str_contains($lowerMsg, 'item') || str_contains($lowerMsg, 'price')
            || str_contains($lowerMsg, 'stock') || str_contains($lowerMsg, 'quantity') || str_contains($lowerMsg, 'qty')
            || str_contains($lowerMsg, 'how much') || str_contains($lowerMsg, 'how many') || str_contains($lowerMsg, 'cost')
            || str_contains($lowerMsg, 'available') || str_contains($lowerMsg, 'availability') || str_contains($lowerMsg, 'left')
            || str_contains($lowerMsg, 'inventory') || str_contains($lowerMsg, 'buy') || str_contains($lowerMsg, 'sell')
            || str_contains($lowerMsg, 'fabric') || str_contains($lowerMsg, 'bag') || str_contains($lowerMsg, 'supply')
            || str_contains($lowerMsg, 'notebook') || str_contains($lowerMsg, 'pen') || str_contains($lowerMsg, 'order')
            || str_contains($lowerMsg, 'eraser') || str_contains($lowerMsg, 'pencil') || str_contains($lowerMsg, 'faber')
            || preg_match('/\b(do (you|u) (still )?(have|sell|carry|offer)|(have|got) any|is there|are there|looking for|searching for|can i (get|buy|order)|i (need|want))\b/', $lowerMsg) === 1;

        // Greeting intents
        if (preg_match('/\b(hi|hello|hey|greetings|good morning|good afternoon|good evening|hello bot|help|start)\b/', $lowerMsg)
            && !($hasProductIntent && !empty($queryTokens))) {
            return [
                'reply' => "Hello! 👋 Welcome to Mera's Merchandise support assistant. How can I help you today? You can ask me about our products, store hours, location, or payment options.",
                'suggestions' => $defaultSuggestions,
            ];
        }

        // Store hours intents
        if (str_contains($lowerMsg, 'hour') || str_contains($lowerMsg, 'time') || str_contains($lowerMsg, 'open') || str_contains($lowerMsg, 'schedule') || str_contains($lowerMsg, 'close') || str_contains($lowerMsg, 'when')) {
            return [
                'reply' => "🕒 Store Hours:\nWe are open Monday to Saturday, from 8:00 AM to 6:00 PM. We are closed on Sundays to restock our goods!",
                'suggestions' => ["Location 📍", "Check Products 🛍️", "Contact Details 📞"],
            ];
        }

        // Location/address intents
        if (str_contains($lowerMsg, 'where') || str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'address') || str_contains($lowerMsg, 'find') || str_contains($lowerMsg, 'stall') || str_contains($lowerMsg, 'place') || str_contains($lowerMsg, 'direction')) {
            return [
                'reply' => "📍 Our Location:\nYou can find us at Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052. Drop by and say hello! 😊",
                'suggestions' => ["Store Hours 🕒", "Check Products 🛍️", "Payment Methods 💳"],
            ];
        }

        // Payment intents
        if (str_contains($lowerMsg, 'pay') || str_contains($lowerMsg, 'payment') || str_contains($lowerMsg, 'gcash') || str_contains($lowerMsg, 'cash')) {
            return [
                'reply' => "💳 Payment Methods:\nWe accept Cash in store and GCash payments. For GCash, we have a QR code at the checkout counter. You can scan and pay securely!",
                'suggestions' => ["Store Hours 🕒", "Location 📍", "Check Products 🛍️"],
            ];
        }

        // Contact details
        if (str_contains($lowerMsg, 'contact') || str_contains($lowerMsg, 'phone') || str_contains($lowerMsg, 'call') || str_contains($lowerMsg, 'number') || str_contains($lowerMsg, 'email') || str_contains($lowerMsg, 'fb') || str_contains($lowerMsg, 'facebook')) {
            return [
                'reply' => "📞 Contact Info:\nEmail: support@meras-merchandise.com\nFacebook: fb.com/meras.merchandise\nYou can also submit an inquiry through our Contact page or Inquiry form.",
                'suggestions' => ["Location 📍", "Check Products 🛍️", "Store Hours 🕒"],
            ];
        }

        // Product inventory search
        $allProducts = Product::all();
        $matches = $this->searchProductsByRelevance($lowerMsg, $queryTokens, $allProducts);
        $exactMatches = $matches['exact'];

        // Only products that match everything the customer asked for are presented as the answer
        if (count($exactMatches) > 0) {
            $productItems = [];

            if (count($exactMatches) === 1) {
                $p = $exactMatches[0];
                $card = $this->productCard($p);
                $unit = $card['unit'];

                $productListMsg = "🛍️ {$p->name}:\n"
                    . "• Price: ₱" . number_format($p->price, 2) . " / {$unit}\n"
                    . "• Quantity: {$p->quantity} {$unit} ({$card['status']})\n";

                if ($p->hasBulkPricing()) {
                    $productListMsg .= "• Bulk Price: ₱" . number_format($p->bulk_price, 2) . " (at {$p->bulk_min_qty}+ {$unit})\n";
                }

                if ($p->quantity > 0) {
                    $productListMsg .= "\n👉 Click on the product card below to view and purchase this item directly in the catalog!";
                } else {
                    $productListMsg .= "\nWe're sorry, but this item is currently out of stock. Please check back with us soon, or contact our team on Messenger for restocking updates.";
                }

                $productItems[] = $card;
            } else {
                $productListMsg = "🛍️ Matching Products Found:\n";
                foreach (array_slice($exactMatches, 0, 4) as $p) {
                    $card = $this->productCard($p);
                    $productListMsg .= "• {$p->name} — ₱" . number_format($p->price, 2) . " ({$p->quantity} {$card['unit']} left, {$card['status']})\n";
                    $productItems[] = $card;
                }
                $productListMsg .= "\n👉 Click on any item below to view it directly in our catalog!";
            }

            return [
                'reply' => $productListMsg,
                'suggestions' => ["Store Hours 🕒", "Location 📍", "Payment Methods 💳"],
                'products' => $productItems,
            ];
        }

        // A specific item was asked for but the store does not carry it
        if (count($matches['similar']) > 0 || ($hasProductIntent && !empty($queryTokens))) {
            return $this->productNotAvailableResponse($queryTokens, $matches['similar'], $allProducts);
        }

        if ($hasProductIntent) {
            // Featured items if general product intent without specific match
            $featured = Product::where('quantity', '>', 0)->limit(4)->get();
            $featuredMsg = "🛍️ Featured Store Products:\n";
            $productItems = [];
            foreach ($featured as $f) {
                $unit = $f->unit ?: 'pcs';
                $status = "🟢 In Stock (" . $f->quantity . " " . $unit . " left)";
                $featuredMsg .= "• {$f->name} — ₱" . number_format($f->price, 2) . "\n";
                $productItems[] = [
                    'id' => $f->id,
                    'name' => $f->name,
                    'price' => '₱' . number_format($f->price, 2),
                    'quantity' => $f->quantity,
                    'unit' => $unit,
                    'status' => $status,
                    'category' => $f->category ?? 'Merchandise',
                    'url' => route('home') . '#product-' . $f->id,
                ];
            }
            $featuredMsg .= "\n👉 Click on any product below to view it, or ask about a specific item by name!";

            return [
                'reply' => $featuredMsg,
                'suggestions' => ["Store Hours 🕒", "Location 📍", "Payment Methods 💳"],
                'products' => $productItems,
            ];
        }

        // Default fallback
        return [
            'reply' => "🤖 Support Assistant:\nThank you for your message! If you need specific assistance, you can ask about our products, store hours, location, or payment options.\n\nOur staff has also been notified and will be happy to assist you!",
            'suggestions' => $defaultSuggestions,
        ];
    }

    /**
     * Professional reply for a product the store does not carry. Lists the closest
     * items it does carry when there are any, otherwise the categories on offer.
     */
    private function productNotAvailableResponse(array $queryTokens, array $similarProducts, $allProducts): array
    {
        $nameTokens = $this->requiredTokens($queryTokens);
        $requested = (count($nameTokens) > 0 && count($nameTokens) <= 5)
            ? '"' . ucwords(implode(' ', $nameTokens)) . '"'
            : 'the item you are looking for';

        $reply = "🔍 Product Not Available:\nWe're sorry, but {$requested} is currently not available in our product catalog.";
        $productItems = [];

        if (count($similarProducts) > 0) {
            $reply .= "\n\nYou may be interested in these similar items that we carry:\n";
            foreach (array_slice($similarProducts, 0, 4) as $p) {
                $card = $this->productCard($p);
                $reply .= "• {$p->name} — {$card['price']} ({$card['status']})\n";
                $productItems[] = $card;
            }
            $reply .= "\n👉 Click on any item below to view it in our catalog, or contact our team on Messenger if you need further assistance.";
        } else {
            $categories = $allProducts->pluck('category')->filter()->unique()->take(6)->implode(', ');
            if ($categories !== '') {
                $reply .= "\n\nWe currently offer products under the following categories: {$categories}.";
            }
            $reply .= "\n\nYou are welcome to browse our available products, or contact our team on Messenger if you would like to inquire further about this item.";
        }

        return [
            'reply' => $reply,
            'suggestions' => ["Check Products 🛍️", "Messenger 💬", "Store Hours 🕒"],
            'products' => $productItems,
        ];
    }

    private function productCard(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'price' => '₱' . number_format($p->price, 2),
            'quantity' => $p->quantity,
            'unit' => $p->unit ?: 'pcs',
            'status' => $p->quantity > 10 ? "🟢 In Stock" : ($p->quantity > 0 ? "🟡 Low Stock ({$p->quantity} left)" : "🔴 Out of Stock"),
            'category' => $p->category ?? 'Merchandise',
            'url' => route('home') . '#product-' . $p->id,
        ];
    }

    private function normalizeSearchText(string $text): string
    {
        $clean = preg_replace('/[^a-z0-9 ]+/', ' ', strtolower($text));

        return preg_replace('/\s+/', ' ', trim($clean ?? '')) ?? '';
    }

    /**
     * Keep only the words that describe what the customer is looking for, dropping
     * greetings, courtesy words, fillers and shopping verbs.
     */
    private function extractQueryTokens(string $message): array
    {
        // Noise / Stop words to exclude from keyword comparison
        $stopWords = [
            'do', 'you', 'have', 'are', 'there', 'any', 'is', 'the', 'a', 'an', 'in', 'on', 'at',
            'to', 'for', 'of', 'with', 'and', 'or', 'can', 'i', 'get', 'show', 'me', 'please',
            'want', 'looking', 'find', 'buy', 'sell', 'how', 'much', 'many', 'cost', 'price',
            'pricing', 'available', 'availability', 'stock', 'store', 'product', 'products',
            'item', 'items', 'merchandise', 'what', 'which', 'who', 'where', 'when', 'why',
            'check', 'search', 'give', 'tell', 'about', 'need', 'would', 'like',
            // Greetings, courtesy words and fillers
            'hi', 'hello', 'hey', 'good', 'day', 'morning', 'afternoon', 'evening', 'help',
            'sir', 'maam', 'ma', 'am', 'madam', 'miss', 'po', 'pls', 'plz', 'kindly', 'thanks', 'thank',
            'ok', 'okay', 'yes', 'yeah', 'no', 'not', 'just', 'only', 'also', 'too', 'so', 'very', 'really',
            'still', 'yet', 'now', 'today', 'currently', 'right', 'some', 'anything', 'something', 'else',
            'other', 'more', 'one', 'ones', 'kind', 'kinds', 'sort', 'if', 'but', 'from', 'by', 'as',
            'bye', 'goodbye', 'welcome', 'nice', 'great', 'cool', 'awesome', 'wow', 'haha', 'lol', 'hmm',
            'sure', 'alright', 'noted', 'again', 'tomorrow', 'tonight', 'yesterday', 'bot', 'chatbot',
            // Pronouns and helper verbs
            'ur', 'your', 'yours', 'we', 'us', 'our', 'my', 'it', 'its', 'im', 'this', 'that', 'these', 'those',
            'here', 'be', 'been', 'was', 'were', 'does', 'did', 'has', 'had', 'got', 'will', 'could', 'should',
            'may', 'might', 'don', 'dont', 'isn', 'aren', 'doesn', 'let', 'know', 'see', 'ask', 'asking', 'inquire',
            // Shopping verbs, stock and quantity words
            'selling', 'sells', 'sold', 'sale', 'carry', 'carries', 'offer', 'offers', 'stocks', 'left',
            'remaining', 'quantity', 'qty', 'inventory', 'prices', 'costs', 'order', 'orders', 'purchase',
            'searching', 'pc', 'pcs', 'piece', 'pieces',
        ];
        $stopWordsLookup = array_flip($stopWords);

        $tokens = [];
        foreach (explode(' ', $this->normalizeSearchText($message)) as $tok) {
            if (strlen($tok) >= 2 && !isset($stopWordsLookup[$tok])) {
                $tokens[] = $tok;
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Bare numbers ("10 erasers") are usually quantities, so they only have to match
     * when nothing else was asked for.
     */
    private function requiredTokens(array $queryTokens): array
    {
        $words = array_values(array_filter($queryTokens, fn ($t) => !ctype_digit((string) $t)));

        return count($words) > 0 ? $words : $queryTokens;
    }

    private function singularize(string $word): string
    {
        $len = strlen($word);

        if ($len > 4 && str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }
        if ($len > 3 && preg_match('/(s|x|z|ch|sh)es$/', $word)) {
            return substr($word, 0, -2);
        }
        if ($len > 3 && str_ends_with($word, 's') && !str_ends_with($word, 'ss')) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    /**
     * Score how well a query word matches one product word. Strong matches (26+) are
     * the same word or its plural, a near-complete prefix ("castel" / "castell") or a
     * small typo in a longer word. Weak matches (18) are a short prefix ("pen" / "pencil")
     * or the end of a compound word ("pen" / "ballpen"): close enough to suggest as a
     * similar item, but not enough to claim it is the product that was asked for.
     */
    private function wordMatchScore(string $token, string $word): int
    {
        if ($word === '') {
            return 0;
        }

        $singularToken = $this->singularize($token);
        $singularWord = $this->singularize($word);

        if ($token === $word || $singularToken === $singularWord) {
            return 40;
        }

        $shorter = min(strlen($token), strlen($word));
        $longer = max(strlen($token), strlen($word));
        $isPrefix = str_starts_with($word, $token) || str_starts_with($token, $word);

        if ($isPrefix && $shorter >= 3 && $shorter / $longer >= 0.6) {
            return 28;
        }

        // Typo tolerance: Levenshtein distance <= 1 for 5-6 char words, <= 2 for 7+ chars.
        // The first letter must agree so "glue" never passes for "blue".
        $qLen = strlen($token);
        if ($qLen >= 5 && $token[0] === $word[0] && levenshtein($token, $word) <= ($qLen >= 7 ? 2 : 1)) {
            return 26;
        }

        if ($isPrefix && $shorter >= 3) {
            return 18;
        }

        if (strlen($singularToken) >= 3 && strlen($word) - strlen($singularToken) >= 3 && str_ends_with($word, $singularToken)) {
            return 18;
        }

        return 0;
    }

    /**
     * Search products using intelligent multi-term relevance scoring and typo tolerance.
     * Accurately ranks specific items (e.g. "Faber-Castell Eraser" for "faber castel eraser")
     * first on the list ahead of general brand or category matches. Applicable to all queries.
     *
     * Returns 'exact' products that match every word the customer asked for, and
     * 'similar' products that only match some of them, each ranked by relevance.
     */
    private function searchProductsByRelevance(string $message, array $queryTokens, $allProducts): array
    {
        $result = ['exact' => [], 'similar' => []];
        $cleanMsg = $this->normalizeSearchText($message);

        if ($cleanMsg === '' || empty($queryTokens)) {
            return $result;
        }

        $requiredTokens = $this->requiredTokens($queryTokens);
        $totalTokens = count($queryTokens);
        $scored = [];

        foreach ($allProducts as $p) {
            $pNameNorm = $this->normalizeSearchText($p->name);
            $pNameWords = explode(' ', $pNameNorm);
            $pSkuWords = explode(' ', $this->normalizeSearchText($p->sku ?? ''));
            $pCatWords = explode(' ', $this->normalizeSearchText($p->category ?? ''));

            $score = 0;
            $matchedTokens = [];
            $strongTokens = [];

            // 1. Exact / whole-phrase match bonus
            if ($pNameNorm !== '' && str_contains(" {$pNameNorm} ", " {$cleanMsg} ")) {
                $score += 150;
            } elseif ($pNameNorm !== '' && str_contains(" {$cleanMsg} ", " {$pNameNorm} ")) {
                $score += 100;
            }

            // 2. Token-by-token matching
            foreach ($queryTokens as $qToken) {
                $qToken = (string) $qToken;
                $tokenScore = 0;

                // Match against Product Name words
                foreach ($pNameWords as $pWord) {
                    $tokenScore = max($tokenScore, $this->wordMatchScore($qToken, $pWord));
                }
                $isStrong = $tokenScore >= 26;

                // Match against SKU if not matched in name
                if (!$isStrong) {
                    foreach ($pSkuWords as $sWord) {
                        if ($sWord !== '' && ($qToken === $sWord || (strlen($qToken) >= 4 && str_contains($sWord, $qToken)))) {
                            $tokenScore = max($tokenScore, 20);
                            $isStrong = true;
                            break;
                        }
                    }
                }

                // Match against Category if not matched in name or SKU
                if (!$isStrong) {
                    foreach ($pCatWords as $cWord) {
                        if ($this->wordMatchScore($qToken, $cWord) >= 26) {
                            $tokenScore = max($tokenScore, 14);
                            $isStrong = true;
                            break;
                        }
                    }
                }

                if ($tokenScore > 0) {
                    $matchedTokens[] = $qToken;
                    $score += $tokenScore;
                }
                if ($isStrong) {
                    $strongTokens[] = $qToken;
                }
            }

            if (count($matchedTokens) === 0) {
                continue;
            }

            // Completeness multiplier: reward items that match all or more of the search terms
            $coverage = count($matchedTokens) / $totalTokens;
            $score = $score * (0.4 + 0.6 * $coverage);

            $isExact = count(array_diff($requiredTokens, $strongTokens)) === 0;
            if ($isExact) {
                $score += 90; // All requested keywords are present!
            }

            // In-stock preference
            if ($p->quantity > 0) {
                $score += 3;
            }

            if ($score >= 18) {
                $scored[] = [
                    'product' => $p,
                    'score' => $score,
                    'matched_count' => count($matchedTokens),
                    'is_exact' => $isExact,
                ];
            }
        }

        // Sort descending by score, then matched token count, then quantity
        usort($scored, function ($a, $b) {
            if ($b['score'] != $a['score']) {
                return ($b['score'] > $a['score']) ? 1 : -1;
            }
            if ($b['matched_count'] != $a['matched_count']) {
                return $b['matched_count'] - $a['matched_count'];
            }
            return $b['product']->quantity - $a['product']->quantity;
        });

        foreach ($scored as $item) {
            $result[$item['is_exact'] ? 'exact' : 'similar'][] = $item['product'];
        }

        return $result;
    }
}
