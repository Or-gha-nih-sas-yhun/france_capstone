<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /** Hard ceiling so a malformed upload cannot tie up the request. */
    private const MAX_IMPORT_ROWS = 5000;

    /**
     * Header spellings accepted for each column, so a sheet exported from Excel
     * or Google Sheets imports without having to be renamed first.
     */
    private const COLUMN_ALIASES = [
        'name' => ['name', 'product', 'product name', 'products', 'item', 'item name', 'description'],
        'price' => ['price', 'unit price', 'srp', 'amount', 'cost', 'selling price'],
        'quantity' => ['quantity', 'qty', 'stock', 'stocks', 'on hand', 'stock qty', 'quantities'],
        'unit' => ['unit', 'units', 'uom', 'unit of measure', 'measure', 'packaging'],
    ];

    /** The unit column is optional; the rest of COLUMN_ALIASES must be present. */
    private const REQUIRED_COLUMNS = ['name', 'price', 'quantity'];

    /** Fallback for a row that names no unit at all. */
    private const DEFAULT_UNIT = 'pcs';

    /**
     * Folds the spellings a stock sheet uses into one canonical abbreviation, so
     * "Piece", "pieces" and "pc" all end up as "pcs". Anything not listed here is
     * kept exactly as typed rather than rejected.
     */
    private const UNIT_ALIASES = [
        'pcs' => ['pc', 'pcs', 'piece', 'pieces', 'pieze', 'ea', 'each'],
        'rms' => ['rm', 'rms', 'ream', 'reams'],
        'pck' => ['pck', 'pcks', 'pack', 'packs', 'pk', 'pks', 'package', 'packages'],
        'box' => ['box', 'boxes', 'bx'],
        'set' => ['set', 'sets'],
        'pair' => ['pair', 'pairs', 'pr', 'prs'],
        'doz' => ['doz', 'dz', 'dozen', 'dozens'],
        'bdl' => ['bdl', 'bundle', 'bundles'],
        'roll' => ['roll', 'rolls'],
        'btl' => ['btl', 'bottle', 'bottles'],
        'can' => ['can', 'cans', 'tin', 'tins'],
        'sack' => ['sack', 'sacks'],
        'pad' => ['pad', 'pads'],
        'tube' => ['tube', 'tubes'],
        'kg' => ['kg', 'kgs', 'kilo', 'kilos', 'kilogram', 'kilograms'],
        'g' => ['g', 'gr', 'gram', 'grams'],
        'l' => ['l', 'lt', 'liter', 'liters', 'litre', 'litres'],
        'ml' => ['ml', 'milliliter', 'milliliters', 'millilitre', 'millilitres'],
        'm' => ['m', 'meter', 'meters', 'metre', 'metres'],
        'yd' => ['yd', 'yds', 'yard', 'yards'],
    ];

    /** Next free sequence per SKU prefix, so a large import stays linear. */
    private array $skuCursor = [];

    public function index(Request $request)
    {
        $search = $request->input('q');
        $query = Product::query();

        if ($search) {
            $searchTerms = '%' . $search . '%';
            $query->where(function ($sub) use ($searchTerms) {
                $sub->where('name', 'like', $searchTerms)
                    ->orWhere('sku', 'like', $searchTerms)
                    ->orWhere('category', 'like', $searchTerms);
            });
        }

        $products = $query->orderByDesc('id')->paginate(8)->withQueryString();

        $editProduct = null;
        if ($request->filled('edit')) {
            $editProduct = Product::find($request->query('edit'));
        }

        return view('inventory.index', compact('products', 'search', 'editProduct'));
    }

    public function store(Request $request)
    {
        $isEdit = $request->input('action') === 'edit';

        $rules = [
            'name' => 'required|string|max:200',
            'sku' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            // Bulk pricing is optional, but a bulk price only takes effect in the
            // POS together with the quantity that unlocks it, so require the pair.
            'bulk_price' => 'nullable|numeric|min:0|required_with:bulk_min_qty',
            'bulk_min_qty' => 'nullable|integer|min:2|required_with:bulk_price',
        ];

        if ($isEdit) {
            $rules['id'] = 'required|integer|exists:products,id';
            $rules['additional_quantity'] = 'required|integer|min:0';
        } else {
            $rules['quantity'] = 'required|integer|min:0';
        }

        $data = $request->validate($rules);

        $data['unit'] = $this->normaliseUnit((string) ($data['unit'] ?? ''));

        // Blank inputs must clear the columns rather than store 0.
        $data['bulk_price'] = $data['bulk_price'] ?? null;
        $data['bulk_min_qty'] = $data['bulk_min_qty'] ?? null;

        if ($isEdit) {
            $product = Product::findOrFail($data['id']);
            $oldQuantity = $product->quantity;
            $data['quantity'] = $oldQuantity + $data['additional_quantity'];
            unset($data['id'], $data['additional_quantity']);
            $product->update($data);
            \App\Models\ActivityLog::log('update_product', 'Updated product: ' . $product->name . ' (SKU: ' . $product->sku . ', Qty: ' . $oldQuantity . ' -> ' . $product->quantity . ')');
            return redirect()->route('products.index')->with('notice', 'Product updated successfully.')->with('noticeType', 'success');
        }

        $product = Product::create($data);
        \App\Models\ActivityLog::log('create_product', 'Created new product: ' . $product->name . ' (SKU: ' . $product->sku . ', Price: PHP ' . number_format($product->price, 2) . ', Qty: ' . $product->quantity . ')');
        return redirect()->route('products.index')->with('notice', 'Product added successfully.')->with('noticeType', 'success');
    }

    /**
     * Bulk-loads products from a CSV holding name, price and quantity, plus an
     * optional unit column (pcs, rms, pcks...) that defaults to pcs.
     *
     * The sheet never carries a SKU column. A row whose name already exists
     * updates that product and keeps the SKU it already has; anything new gets a
     * SKU generated from the product name.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:2048',
        ], [
            'csv_file.required' => 'Choose a CSV file to import.',
            'csv_file.max' => 'The file may not be larger than 2 MB.',
        ]);

        $file = $request->file('csv_file');

        // Excel commonly reports a CSV as application/vnd.ms-excel, which makes the
        // `mimes` rule reject a perfectly good file, so gate on the extension. The
        // upload is only ever parsed as text, never executed.
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            return $this->importFailed('The file must be a .csv file.');
        }

        $path = $file->getRealPath();
        $delimiter = $this->detectDelimiter($path);

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return $this->importFailed('That file could not be opened. Please try uploading it again.');
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);
            return $this->importFailed('That file is empty.');
        }

        // Excel writes a UTF-8 BOM that would otherwise glue itself to the first
        // header cell and stop "name" from matching.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);

        $columns = $this->mapColumns($header);
        $missing = array_values(array_filter(
            self::REQUIRED_COLUMNS,
            fn ($column) => $columns[$column] === null
        ));

        if ($missing !== []) {
            fclose($handle);

            return $this->importFailed(
                'The CSV is missing a ' . implode(' and ', $missing) . ' column. '
                . 'The first row must name the columns, for example: name,price,quantity'
            );
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $line = 1;

        try {
            DB::beginTransaction();

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;

                if ($line - 1 > self::MAX_IMPORT_ROWS) {
                    $errors[] = 'Stopped at ' . self::MAX_IMPORT_ROWS . ' rows. Split the file and import the rest separately.';
                    break;
                }

                // fgetcsv hands back [null] for a blank line.
                if ($row === [null] || trim(implode('', array_map('strval', $row))) === '') {
                    continue;
                }

                $name = trim((string) ($row[$columns['name']] ?? ''));
                $rawPrice = trim((string) ($row[$columns['price']] ?? ''));
                $rawQuantity = trim((string) ($row[$columns['quantity']] ?? ''));
                $unit = $this->normaliseUnit(
                    $columns['unit'] === null ? '' : trim((string) ($row[$columns['unit']] ?? ''))
                );

                if ($name === '') {
                    $errors[] = 'Row ' . $line . ': the name is blank.';
                    continue;
                }

                $price = $this->parseNumber($rawPrice);
                if ($price === null || $price < 0) {
                    $errors[] = 'Row ' . $line . ' (' . $name . '): "' . $rawPrice . '" is not a valid price.';
                    continue;
                }

                $quantity = $this->parseNumber($rawQuantity);
                if ($quantity === null || $quantity < 0) {
                    $errors[] = 'Row ' . $line . ' (' . $name . '): "' . $rawQuantity . '" is not a valid quantity.';
                    continue;
                }

                // Matching on the name is what lets a re-import correct prices and
                // stock instead of piling up duplicates under fresh SKUs.
                $existing = Product::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();

                if ($existing) {
                    $existing->update([
                        'price' => $price,
                        'quantity' => (int) $quantity,
                        'unit' => $unit,
                    ]);
                    $updated++;
                    continue;
                }

                Product::create([
                    'name' => mb_substr($name, 0, 200),
                    'sku' => $this->generateSku($name),
                    'unit' => $unit,
                    'price' => $price,
                    'quantity' => (int) $quantity,
                ]);
                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            Log::error('Product CSV import failed: ' . $e->getMessage());

            return $this->importFailed('The import failed and nothing was saved. Please check the file and try again.');
        }

        fclose($handle);

        if ($created === 0 && $updated === 0) {
            return $this->importFailed('No products were imported. Check the rows listed below.', $errors);
        }

        \App\Models\ActivityLog::log(
            'import_products',
            'Imported products from CSV: ' . $created . ' added, ' . $updated . ' updated, ' . count($errors) . ' skipped.'
        );

        $summary = [];
        if ($created > 0) {
            $summary[] = $created . ' new product' . ($created === 1 ? '' : 's') . ' added';
        }
        if ($updated > 0) {
            $summary[] = $updated . ' existing product' . ($updated === 1 ? '' : 's') . ' updated';
        }

        $notice = 'Import complete: ' . implode(' and ', $summary) . '.';
        if ($errors !== []) {
            $notice .= ' ' . count($errors) . ' row(s) were skipped.';
        }

        return redirect()->route('products.index')
            ->with('notice', $notice)
            ->with('noticeType', $errors === [] ? 'success' : 'warning')
            ->with('import_errors', $errors);
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        $sku = $product->sku;
        $product->delete();
        \App\Models\ActivityLog::log('delete_product', 'Deleted product: ' . $name . ' (SKU: ' . $sku . ')');
        return redirect()->route('products.index')->with('notice', 'Product successfully deleted.')->with('noticeType', 'success');
    }

    /**
     * Builds a unique SKU from the product name, e.g. "Coca-Cola 1.5L" -> COC-001.
     */
    private function generateSku(string $name): string
    {
        $letters = preg_replace('/[^A-Za-z0-9]/', '', $name);
        $prefix = strtoupper(mb_substr($letters, 0, 3));
        $prefix = $prefix === '' ? 'SKU' : str_pad($prefix, 3, 'X');

        // The cursor carries across rows so importing many same-prefix names does
        // not rescan from 001 every time.
        $this->skuCursor[$prefix] ??= 1;

        do {
            $sku = $prefix . '-' . str_pad((string) $this->skuCursor[$prefix], 3, '0', STR_PAD_LEFT);
            $this->skuCursor[$prefix]++;
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Resolves each required column to its position in the header row.
     */
    private function mapColumns(array $header): array
    {
        $normalised = [];
        foreach ($header as $index => $cell) {
            $normalised[$index] = $this->normaliseHeader((string) $cell);
        }

        $columns = [];
        foreach (self::COLUMN_ALIASES as $column => $aliases) {
            $columns[$column] = null;
            foreach ($normalised as $index => $cell) {
                if (in_array($cell, $aliases, true)) {
                    $columns[$column] = $index;
                    break;
                }
            }
        }

        return $columns;
    }

    private function normaliseHeader(string $cell): string
    {
        $cell = str_replace(['_', '-'], ' ', trim(mb_strtolower($cell)));

        return trim(preg_replace('/\s+/', ' ', $cell));
    }

    /**
     * Folds a unit cell to its canonical abbreviation ("Pieces" -> "pcs"). An
     * unrecognised unit is kept as typed so an unusual one still imports, and a
     * blank cell falls back to the default.
     */
    private function normaliseUnit(string $value): string
    {
        $cleaned = trim(preg_replace('/[.\s]+/', ' ', mb_strtolower($value)));

        if ($cleaned === '') {
            return self::DEFAULT_UNIT;
        }

        foreach (self::UNIT_ALIASES as $canonical => $aliases) {
            if (in_array($cleaned, $aliases, true)) {
                return $canonical;
            }
        }

        return mb_substr($cleaned, 0, 20);
    }

    /**
     * Reads a spreadsheet number, tolerating "PHP 1,250.00" and "P1250".
     * Returns null when the cell is not a number at all.
     */
    private function parseNumber(string $value): ?float
    {
        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);

        if ($cleaned === '' || $cleaned === '-' || !is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }

    /**
     * Sniffs the separator so semicolon and tab exports also load.
     */
    private function detectDelimiter(string $path): string
    {
        $probe = fopen($path, 'r');
        $firstLine = $probe === false ? '' : (string) fgets($probe);
        if ($probe !== false) {
            fclose($probe);
        }

        $counts = [
            ',' => substr_count($firstLine, ','),
            ';' => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
        ];

        arsort($counts);
        $best = array_key_first($counts);

        return $counts[$best] > 0 ? $best : ',';
    }

    private function importFailed(string $message, array $errors = [])
    {
        return redirect()->route('products.index')
            ->with('notice', $message)
            ->with('noticeType', 'danger')
            ->with('import_errors', $errors);
    }
}
