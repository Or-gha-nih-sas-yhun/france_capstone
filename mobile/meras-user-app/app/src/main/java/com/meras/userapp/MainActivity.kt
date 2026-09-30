package com.meras.userapp

import android.annotation.SuppressLint
import android.app.Activity
import android.app.DownloadManager
import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Environment
import android.util.TypedValue
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.webkit.CookieManager
import android.webkit.PermissionRequest
import android.webkit.URLUtil
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.FrameLayout
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.google.firebase.messaging.FirebaseMessaging
import org.json.JSONObject

class MainActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    private lateinit var swipeRefreshLayout: SwipeRefreshLayout
    private lateinit var progressBar: ProgressBar
    private lateinit var loadingView: View
    private lateinit var offlineView: View
    private lateinit var bottomNav: LinearLayout
    private lateinit var fabChatbot: View
    private lateinit var chatOverlay: LinearLayout
    // Created on first use so app start only pays for one WebView
    private var chatWebView: WebView? = null
    private val customerUrl: String by lazy { getString(R.string.customer_url) }
    private val rootBaseUrl: String by lazy { customerUrl.substringBefore("/user-app").substringBefore("?") }
    private var fcmToken: String? = null
    // Token the server session already holds, so it is only sent again when it changes
    private var registeredFcmToken: String? = null
    private var pageReady = false
    private var mainFrameFailed = false
    // Set when the chat overlay went through login, so the main page picks up the new session
    private var chatSessionChanged = false

    // File upload callback support for WebViews
    private var filePathCallback: ValueCallback<Array<Uri>>? = null

    private val fileChooserLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (filePathCallback == null) return@registerForActivityResult
        
        var results: Array<Uri>? = null
        if (result.resultCode == Activity.RESULT_OK) {
            val data = result.data
            if (data != null) {
                val dataString = data.dataString
                val clipData = data.clipData
                if (clipData != null) {
                    results = Array(clipData.itemCount) { i -> clipData.getItemAt(i).uri }
                } else if (dataString != null) {
                    results = arrayOf(Uri.parse(dataString))
                }
            }
        }
        filePathCallback?.onReceiveValue(results)
        filePathCallback = null
    }

    // Tab Views
    private val tabViews = ArrayList<LinearLayout>()

    @SuppressLint("SetJavaScriptEnabled", "ClickableViewAccessibility")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        try {
            // Main vertical container
            val mainLayout = LinearLayout(this).apply {
                orientation = LinearLayout.VERTICAL
                layoutParams = ViewGroup.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.MATCH_PARENT
                )
            }

            // Top Horizontal Loading Progress Bar
            progressBar = ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal).apply {
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    dpToPx(3)
                )
                progressDrawable.setColorFilter(
                    Color.parseColor("#4f46e5"),
                    android.graphics.PorterDuff.Mode.SRC_IN
                )
                visibility = View.GONE
            }
            mainLayout.addView(progressBar)

            // Main content area
            val contentFrame = FrameLayout(this).apply {
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    0,
                    1.0f
                )
            }

            // Pull-To-Refresh Layout
            swipeRefreshLayout = SwipeRefreshLayout(this).apply {
                layoutParams = ViewGroup.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.MATCH_PARENT
                )
                setColorSchemeColors(Color.parseColor("#4f46e5"))
                setOnRefreshListener {
                    if (isNetworkAvailable()) {
                        webView.reload()
                    } else {
                        isRefreshing = false
                        Toast.makeText(this@MainActivity, "No internet connection", Toast.LENGTH_SHORT).show()
                    }
                }
            }

            webView = WebView(this)

            // Disable SwipeRefresh when WebView is scrolled down
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                webView.setOnScrollChangeListener { _, _, scrollY, _, _ ->
                    swipeRefreshLayout.isEnabled = (scrollY == 0)
                }
            }

            offlineView = createOfflineView()

            swipeRefreshLayout.addView(webView, ViewGroup.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))

            contentFrame.addView(swipeRefreshLayout)
            contentFrame.addView(offlineView, FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))

            // Branded splash until the first page can be drawn, instead of a blank white screen
            loadingView = createLoadingView()
            contentFrame.addView(loadingView, FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            ))

            // Chat Overlay Header / Title Bar
            val chatHeader = LinearLayout(this).apply {
                orientation = LinearLayout.HORIZONTAL
                layoutParams = LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    dpToPx(56)
                )
                setBackgroundColor(Color.parseColor("#4f46e5"))
                gravity = Gravity.CENTER_VERTICAL
                setPadding(dpToPx(16), 0, dpToPx(16), 0)

                // Title Text
                addView(TextView(this@MainActivity).apply {
                    text = "💬 Live Chat Support"
                    setTextColor(Color.WHITE)
                    textSize = 16f
                    setTypeface(null, android.graphics.Typeface.BOLD)
                    layoutParams = LinearLayout.LayoutParams(
                        0,
                        ViewGroup.LayoutParams.WRAP_CONTENT,
                        1.0f
                    )
                })

                // Close Button ("✕")
                addView(TextView(this@MainActivity).apply {
                    text = "✕"
                    setTextColor(Color.WHITE)
                    textSize = 18f
                    setTypeface(null, android.graphics.Typeface.BOLD)
                    isClickable = true
                    setPadding(dpToPx(12), dpToPx(12), dpToPx(12), dpToPx(12))
                    
                    val outValue = TypedValue()
                    theme.resolveAttribute(android.R.attr.selectableItemBackgroundBorderless, outValue, true)
                    setBackgroundResource(outValue.resourceId)

                    setOnClickListener {
                        closeChatSupport()
                    }
                })
            }

            // Combined Chat Overlay Container
            chatOverlay = LinearLayout(this).apply {
                orientation = LinearLayout.VERTICAL
                layoutParams = FrameLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.MATCH_PARENT
                ).apply {
                    topMargin = dpToPx(50)
                }
                
                val roundedBg = GradientDrawable().apply {
                    setColor(Color.WHITE)
                    val r = dpToPx(16).toFloat()
                    cornerRadii = floatArrayOf(r, r, r, r, 0f, 0f, 0f, 0f)
                }
                background = roundedBg
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    elevation = dpToPx(12).toFloat()
                }
                visibility = View.GONE

                addView(chatHeader)
            }

            contentFrame.addView(chatOverlay)

            // Floating Chatbot Button (FAB)
            fabChatbot = FrameLayout(this).apply {
                layoutParams = FrameLayout.LayoutParams(
                    dpToPx(56),
                    dpToPx(56)
                ).apply {
                    gravity = Gravity.BOTTOM or Gravity.END
                    setMargins(0, 0, dpToPx(16), dpToPx(16))
                }
                
                // Replicate browser gradient background (linear 135deg top-left to bottom-right)
                val shape = GradientDrawable(
                    GradientDrawable.Orientation.TL_BR,
                    intArrayOf(Color.parseColor("#4f46e5"), Color.parseColor("#3730a3"))
                ).apply {
                    shape = GradientDrawable.OVAL
                }
                
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    val ripple = android.graphics.drawable.RippleDrawable(
                        android.content.res.ColorStateList.valueOf(Color.argb(70, 255, 255, 255)),
                        shape,
                        null
                    )
                    background = ripple
                    elevation = dpToPx(6).toFloat()
                } else {
                    background = shape
                }
                visibility = View.GONE
                
                // Replicate browser's sleek vector icon instead of a basic text emoji
                addView(android.widget.ImageView(this@MainActivity).apply {
                    setImageResource(R.drawable.ic_chatbot)
                    scaleType = android.widget.ImageView.ScaleType.CENTER_INSIDE
                    setPadding(dpToPx(14), dpToPx(14), dpToPx(14), dpToPx(14))
                    layoutParams = FrameLayout.LayoutParams(
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.MATCH_PARENT
                    )
                })

                setOnClickListener {
                    openChatSupport()
                }

                setOnTouchListener(object : View.OnTouchListener {
                    private var dX = 0f
                    private var dY = 0f
                    private var initialX = 0f
                    private var initialY = 0f
                    private var isMoving = false
                    private val CLICK_DRAG_TOLERANCE = 10f

                    @SuppressLint("ClickableViewAccessibility")
                    override fun onTouch(view: View, event: android.view.MotionEvent): Boolean {
                        when (event.action) {
                            android.view.MotionEvent.ACTION_DOWN -> {
                                dX = view.x - event.rawX
                                dY = view.y - event.rawY
                                initialX = view.x
                                initialY = view.y
                                isMoving = false
                            }
                            android.view.MotionEvent.ACTION_MOVE -> {
                                val newX = event.rawX + dX
                                val newY = event.rawY + dY
                                
                                val parent = view.parent as ViewGroup
                                val parentWidth = parent.width
                                val parentHeight = parent.height
                                
                                val boundedX = Math.max(0f, Math.min(newX, (parentWidth - view.width).toFloat()))
                                val boundedY = Math.max(0f, Math.min(newY, (parentHeight - view.height).toFloat()))
                                
                                view.x = boundedX
                                view.y = boundedY
                                
                                if (Math.abs(view.x - initialX) > CLICK_DRAG_TOLERANCE || Math.abs(view.y - initialY) > CLICK_DRAG_TOLERANCE) {
                                    isMoving = true
                                }
                            }
                            android.view.MotionEvent.ACTION_UP -> {
                                // A tap opens the chat (via the click listener); a drag only moves the button
                                if (!isMoving) {
                                    view.performClick()
                                }
                            }
                        }
                        return true
                    }
                })
            }

            contentFrame.addView(fabChatbot)

            // Bottom Navigation Bar
            bottomNav = createBottomNavigationBar()

            mainLayout.addView(contentFrame)
            mainLayout.addView(bottomNav)

            setContentView(mainLayout)

            // Enable Cookies
            CookieManager.getInstance().apply {
                setAcceptCookie(true)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    setAcceptThirdPartyCookies(webView, true)
                }
            }

            setupWebViewSettings(webView)

            // Attach Download Listeners
            webView.setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
                downloadFile(url, userAgent, contentDisposition, mimeType)
            }

            webView.webChromeClient = object : WebChromeClient() {
                override fun onProgressChanged(view: WebView?, newProgress: Int) {
                    if (newProgress < 100) {
                        progressBar.visibility = View.VISIBLE
                        progressBar.progress = newProgress
                    } else {
                        progressBar.visibility = View.GONE
                    }
                }

                override fun onShowFileChooser(
                    webView: WebView?,
                    filePathCallback: ValueCallback<Array<Uri>>?,
                    fileChooserParams: FileChooserParams?
                ): Boolean {
                    return handleFileChooser(filePathCallback, fileChooserParams)
                }

                override fun onPermissionRequest(request: PermissionRequest?) {
                    request?.grant(request.resources)
                }
            }

            webView.webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                    return handleNavigation(request.url)
                }

                override fun onPageStarted(view: WebView, url: String, favicon: Bitmap?) {
                    pageReady = false
                    mainFrameFailed = false
                }

                // Fires once the new page can be drawn, well before all of its images have loaded
                override fun onPageCommitVisible(view: WebView, url: String) {
                    hideLoadingView()
                }

                override fun onPageFinished(view: WebView, url: String) {
                    swipeRefreshLayout.isRefreshing = false
                    hideLoadingView()
                    CookieManager.getInstance().flush()

                    // onPageFinished also fires for the WebView's error page; keep the offline view over it
                    if (mainFrameFailed) return

                    offlineView.visibility = View.GONE
                    hideStaffControls(view)
                    bottomNav.visibility = View.VISIBLE
                    updateFabVisibility()
                    highlightActiveTab(url)

                    pageReady = true
                    syncFcmTokenWithServer()
                }

                // Also reports in-page section changes (#products, #inquire), which never reach onPageFinished
                override fun doUpdateVisitedHistory(view: WebView, url: String, isReload: Boolean) {
                    highlightActiveTab(url)
                }

                override fun onReceivedError(
                    view: WebView,
                    request: WebResourceRequest,
                    error: WebResourceError
                ) {
                    if (request.isForMainFrame) {
                        mainFrameFailed = true
                        swipeRefreshLayout.isRefreshing = false
                        hideLoadingView()
                        offlineView.visibility = View.VISIBLE
                    }
                }
            }

            // Modern Android Back Navigation handler
            onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
                override fun handleOnBackPressed() {
                    val chat = chatWebView
                    if (::chatOverlay.isInitialized && chatOverlay.visibility == View.VISIBLE) {
                        if (chat != null && chat.canGoBack()) {
                            chat.goBack()
                        } else {
                            closeChatSupport()
                        }
                    } else if (webView.canGoBack()) {
                        webView.goBack()
                    } else {
                        // Hand over to the system, then re-arm: on Android 12+ the activity is only
                        // sent to the background, and a disabled callback would skip page history on return
                        isEnabled = false
                        onBackPressedDispatcher.onBackPressed()
                        isEnabled = true
                    }
                }
            })

            // Notification Permission
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                if (checkSelfPermission(android.Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                    requestPermissions(arrayOf(android.Manifest.permission.POST_NOTIFICATIONS), 101)
                }
            }

            // Start loading right away with the token cached from a previous launch. Waiting for
            // Firebase here used to hold the first request back by seconds on a cold start.
            fcmToken = getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE).getString(KEY_FCM_TOKEN, null)
            val restored = savedInstanceState != null && webView.restoreState(savedInstanceState) != null
            val openTab = if (savedInstanceState == null) tabFromIntent(intent) else null
            when {
                openTab != null -> onTabSelected(openTab)
                !restored -> loadStore()
            }

            refreshFcmToken()

        } catch (t: Throwable) {
            Toast.makeText(this, "Startup error: ${t.message}", Toast.LENGTH_LONG).show()
            t.printStackTrace()
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        tabFromIntent(intent)?.let { onTabSelected(it) }
    }

    // Notification taps open the Alerts tab, where inquiry replies are listed
    private fun tabFromIntent(intent: Intent?): String? {
        if (intent == null) return null
        intent.getStringExtra(EXTRA_OPEN_TAB)?.let { return it }
        // Pushes that Android displayed itself (app in background) arrive with the FCM extras
        return if (intent.hasExtra("google.message_id")) "alerts" else null
    }

    private fun openChatSupport() {
        val chat = chatWebView ?: createChatWebView().also {
            chatWebView = it
            chatOverlay.addView(it)
        }

        chat.onResume()
        chat.loadUrl("$rootBaseUrl/chat")
        chatOverlay.visibility = View.VISIBLE
        updateFabVisibility()
    }

    private fun closeChatSupport() {
        chatOverlay.visibility = View.GONE
        chatWebView?.onPause()
        updateFabVisibility()

        if (chatSessionChanged) {
            chatSessionChanged = false
            webView.reload()
        }
    }

    private fun updateFabVisibility() {
        val onChatPage = webView.url?.contains("/chat") == true
        fabChatbot.visibility = if (onChatPage || chatOverlay.visibility == View.VISIBLE) View.GONE else View.VISIBLE
    }

    // Secondary WebView for Floating Chat
    private fun createChatWebView(): WebView {
        return WebView(this).apply {
            layoutParams = LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                0,
                1.0f
            )
            setupWebViewSettings(this)

            setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
                downloadFile(url, userAgent, contentDisposition, mimeType)
            }

            webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                    return handleNavigation(request.url)
                }

                override fun onPageFinished(view: WebView, url: String) {
                    // Guests are sent to login first; signing in here changes the main page's session too
                    if (url.contains("/login") || url.contains("/register")) {
                        chatSessionChanged = true
                    }
                }
            }

            webChromeClient = object : WebChromeClient() {
                override fun onShowFileChooser(
                    webView: WebView?,
                    filePathCallback: ValueCallback<Array<Uri>>?,
                    fileChooserParams: FileChooserParams?
                ): Boolean {
                    return handleFileChooser(filePathCallback, fileChooserParams)
                }
            }
        }
    }

    private fun setupWebViewSettings(targetWebView: WebView) {
        targetWebView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            loadsImagesAutomatically = true
            cacheMode = WebSettings.LOAD_DEFAULT
            mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
            setSupportZoom(false)
            allowFileAccess = true
            allowContentAccess = true
            useWideViewPort = true
            loadWithOverviewMode = true
            mediaPlaybackRequiresUserGesture = false

            val defaultUserAgent = userAgentString
            val customUserAgent = defaultUserAgent
                .replace("; wv", "")
                .replace(Regex("Version/[0-9.]+\\s"), "") + " MerasUserApp/1.0"
            userAgentString = customUserAgent
        }
    }

    private fun handleFileChooser(
        filePathCallback: ValueCallback<Array<Uri>>?,
        fileChooserParams: WebChromeClient.FileChooserParams?
    ): Boolean {
        this.filePathCallback?.onReceiveValue(null)
        this.filePathCallback = filePathCallback

        val intent = fileChooserParams?.createIntent() ?: Intent(Intent.ACTION_GET_CONTENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE)
            type = "*/*"
        }

        try {
            fileChooserLauncher.launch(intent)
        } catch (e: ActivityNotFoundException) {
            this.filePathCallback = null
            Toast.makeText(this, "Cannot open file chooser", Toast.LENGTH_SHORT).show()
            return false
        }
        return true
    }

    private fun downloadFile(url: String, userAgent: String, contentDisposition: String, mimeType: String) {
        try {
            val request = DownloadManager.Request(Uri.parse(url)).apply {
                setMimeType(mimeType)
                addRequestHeader("User-Agent", userAgent)
                addRequestHeader("Cookie", CookieManager.getInstance().getCookie(url))
                setDescription("Downloading file...")
                setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                val fileName = URLUtil.guessFileName(url, contentDisposition, mimeType)
                setTitle(fileName)
                setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName)
            }
            val dm = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
            dm.enqueue(request)
            Toast.makeText(this, "Downloading file...", Toast.LENGTH_SHORT).show()
        } catch (e: Exception) {
            Toast.makeText(this, "Download failed: ${e.message}", Toast.LENGTH_SHORT).show()
        }
    }

    private fun isNetworkAvailable(): Boolean {
        val connectivityManager = getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            val nw = connectivityManager.activeNetwork ?: return false
            val actNw = connectivityManager.getNetworkCapabilities(nw) ?: return false
            return actNw.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) ||
                   actNw.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR) ||
                   actNw.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)
        } else {
            @Suppress("DEPRECATION")
            val nwInfo = connectivityManager.activeNetworkInfo
            @Suppress("DEPRECATION")
            return nwInfo != null && nwInfo.isConnected
        }
    }

    private fun refreshFcmToken() {
        FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
            val token = if (task.isSuccessful) task.result else null
            if (token.isNullOrEmpty()) return@addOnCompleteListener

            fcmToken = token
            saveFcmToken(this, token)
            syncFcmTokenWithServer()
        }
    }

    // Hands a token the current session does not have yet (first launch, rotated token) to the
    // server from inside the page, so it lands in the same session the inquiry form posts from.
    private fun syncFcmTokenWithServer() {
        val token = fcmToken ?: return
        if (!pageReady || token == registeredFcmToken || !isStoreUrl(webView.url)) return

        val endpoint = customerUrl.substringBefore("?").trimEnd('/') + "/fcm-token"
        webView.evaluateJavascript(
            """
            (function () {
                var xsrf = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
                if (!xsrf) return false;
                fetch(${JSONObject.quote(endpoint)}, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': decodeURIComponent(xsrf[1])
                    },
                    body: JSON.stringify({ fcm_token: ${JSONObject.quote(token)} })
                }).catch(function () {});
                return true;
            })();
            """.trimIndent()
        ) { sent ->
            if (sent == "true") registeredFcmToken = token
        }
    }

    // Loads the catalog page; a known token rides along and the server keeps it in the session
    private fun loadStore(section: String = "") {
        registeredFcmToken = fcmToken
        webView.loadUrl(getFinalUrlWithToken(customerUrl) + section)
    }

    private fun isStoreUrl(url: String?): Boolean {
        if (url == null) return false
        val store = Uri.parse(customerUrl)
        val uri = Uri.parse(url)
        return uri.scheme == store.scheme && uri.host == store.host && uri.port == store.port
    }

    // The catalog is served at /user-app, and at / after login and logout redirects
    private fun isCatalogPage(url: String?): Boolean {
        if (!isStoreUrl(url)) return false
        val path = Uri.parse(url).path.orEmpty().trimEnd('/')
        return path.isEmpty() || path == Uri.parse(customerUrl).path.orEmpty().trimEnd('/')
    }

    private fun getFinalUrlWithToken(baseUrl: String): String {
        return if (fcmToken != null) {
            val uri = Uri.parse(baseUrl)
            val builder = uri.buildUpon()
            builder.appendQueryParameter("fcm_token", fcmToken)
            builder.build().toString()
        } else {
            baseUrl
        }
    }

    private fun handleNavigation(uri: Uri): Boolean {
        val scheme = uri.scheme.orEmpty().lowercase()
        if (scheme != "http" && scheme != "https") {
            try {
                val intent = Intent(Intent.ACTION_VIEW, uri)
                startActivity(intent)
            } catch (e: ActivityNotFoundException) {
                Toast.makeText(this, "No application found to handle action", Toast.LENGTH_SHORT).show()
            }
            return true
        }

        return false
    }

    private fun hideStaffControls(view: WebView) {
        view.evaluateJavascript(
            """
            (function() {
                // Hide all app-download buttons (web version download prompts) 
                // since the user already has the app installed
                var selectors = [
                    '.btn-app-download',
                    '.btn-app-download-hero',
                    '.btn-app-download-footer',
                    'a[href*="/download/android-app"]',
                    'a[href*="meras-user-app"]'
                ];
                selectors.forEach(function(sel) {
                    document.querySelectorAll(sel).forEach(function(el) {
                        el.style.display = 'none';
                    });
                });
            })();
            """.trimIndent(),
            null
        )
    }

    private fun createBottomNavigationBar(): LinearLayout {
        val navBar = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            layoutParams = LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                dpToPx(65)
            )
            setBackgroundColor(Color.WHITE)
            gravity = Gravity.CENTER_VERTICAL
            visibility = View.VISIBLE
            
            val border = GradientDrawable().apply {
                setColor(Color.WHITE)
                setStroke(dpToPx(1), Color.parseColor("#e2e8f0"))
            }
            background = border
        }

        val tabs = listOf(
            TabItem(R.drawable.ic_nav_home, "Home", "home"),
            TabItem(R.drawable.ic_nav_products, "Products", "products"),
            TabItem(R.drawable.ic_nav_inquiry, "Inquiry", "inquiry"),
            TabItem(R.drawable.ic_nav_profile, "Profile", "profile"),
            TabItem(R.drawable.ic_nav_alerts, "Alerts", "alerts")
        )

        for (i in tabs.indices) {
            val tab = tabs[i]
            val tabLayout = LinearLayout(this).apply {
                orientation = LinearLayout.VERTICAL
                gravity = Gravity.CENTER
                layoutParams = LinearLayout.LayoutParams(
                    0,
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    1.0f
                )
                setPadding(0, dpToPx(6), 0, dpToPx(6))
                isClickable = true
                
                val outValue = TypedValue()
                theme.resolveAttribute(android.R.attr.selectableItemBackground, outValue, true)
                setBackgroundResource(outValue.resourceId)

                setOnClickListener {
                    onTabSelected(tab.id)
                }
            }

            val iconView = android.widget.ImageView(this).apply {
                setImageResource(tab.iconResId)
                layoutParams = LinearLayout.LayoutParams(dpToPx(22), dpToPx(22))
                setColorFilter(Color.parseColor("#64748b"))
            }

            val titleView = TextView(this).apply {
                text = tab.title
                textSize = 11f
                setTextColor(Color.parseColor("#64748b"))
                gravity = Gravity.CENTER
                setPadding(0, dpToPx(2), 0, 0)
            }

            tabLayout.addView(iconView)
            tabLayout.addView(titleView)
            
            navBar.addView(tabLayout)
            tabViews.add(tabLayout)
        }

        return navBar
    }

    private fun onTabSelected(tabId: String) {
        val section = when (tabId) {
            "home" -> "#home"
            "products" -> "#products"
            "inquiry" -> "#inquire"
            else -> null
        }

        if (section != null) {
            if (isCatalogPage(webView.url) && !mainFrameFailed) {
                // Home, Products and Inquiry are sections of one page: switch in place
                // instead of downloading and rendering the whole catalog again
                showCatalogSection(section)
            } else {
                loadStore(if (section == "#home") "" else section)
            }
            return
        }

        val targetUrl = when (tabId) {
            "profile" -> "$rootBaseUrl/profile"
            "alerts" -> "$rootBaseUrl/notifications"
            else -> customerUrl
        }
        webView.loadUrl(targetUrl)
    }

    // The page's own hashchange handler (handleTabSwitching) shows the matching section
    private fun showCatalogSection(section: String) {
        webView.evaluateJavascript(
            """
            (function (hash) {
                if ((location.hash || '#home') !== hash) {
                    location.hash = hash;
                } else if (typeof handleTabSwitching === 'function') {
                    handleTabSwitching();
                }
                if (hash === '#home') window.scrollTo(0, 0);
            })(${JSONObject.quote(section)});
            """.trimIndent(),
            null
        )
    }

    private fun highlightActiveTab(url: String) {
        val activeColor = Color.parseColor("#4f46e5")
        val inactiveColor = Color.parseColor("#64748b")

        val activeTabId = when {
            url.contains("/profile") -> "profile"
            url.contains("/notifications") -> "alerts"
            url.contains("#product") -> "products" // #products and #product-{id} deep links
            url.contains("#inquir") -> "inquiry" // #inquire and #inquiries
            else -> "home"
        }

        val tabs = listOf("home", "products", "inquiry", "profile", "alerts")
        val activeIndex = tabs.indexOf(activeTabId)

        for (i in tabViews.indices) {
            val tabLayout = tabViews[i]
            val iconView = tabLayout.getChildAt(0) as android.widget.ImageView
            val titleView = tabLayout.getChildAt(1) as TextView
            if (i == activeIndex) {
                iconView.setColorFilter(activeColor)
                titleView.setTextColor(activeColor)
                titleView.paint.isFakeBoldText = true
            } else {
                iconView.setColorFilter(inactiveColor)
                titleView.setTextColor(inactiveColor)
                titleView.paint.isFakeBoldText = false
            }
            titleView.invalidate()
        }
    }

    private fun dpToPx(dp: Int): Int {
        return (dp * resources.displayMetrics.density).toInt()
    }

    private fun createOfflineView(): View {
        val wrapper = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER
            setPadding(40, 40, 40, 40)
            setBackgroundColor(Color.rgb(248, 250, 252))
            visibility = View.GONE
        }

        val title = TextView(this).apply {
            text = getString(R.string.offline_title)
            textSize = 22f
            setTextColor(Color.rgb(15, 23, 42))
            gravity = Gravity.CENTER
        }

        val message = TextView(this).apply {
            text = getString(R.string.offline_message)
            textSize = 15f
            setTextColor(Color.rgb(100, 116, 139))
            gravity = Gravity.CENTER
            setPadding(0, 12, 0, 24)
        }

        val retry = Button(this).apply {
            text = getString(R.string.retry)
            setOnClickListener {
                wrapper.visibility = View.GONE
                // Retry the page that failed rather than always dropping back to Home
                if (webView.url.isNullOrEmpty()) loadStore() else webView.reload()
            }
        }

        wrapper.addView(title)
        wrapper.addView(message)
        wrapper.addView(retry)

        return wrapper
    }

    private fun createLoadingView(): View {
        return LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER
            setBackgroundColor(Color.WHITE)
            isClickable = true // keep taps off the half-loaded page underneath

            addView(ProgressBar(this@MainActivity).apply {
                isIndeterminate = true
                indeterminateDrawable.setColorFilter(
                    Color.parseColor("#4f46e5"),
                    android.graphics.PorterDuff.Mode.SRC_IN
                )
            })

            addView(TextView(this@MainActivity).apply {
                text = getString(R.string.app_name)
                textSize = 16f
                setTextColor(Color.rgb(15, 23, 42))
                setTypeface(null, android.graphics.Typeface.BOLD)
                gravity = Gravity.CENTER
                setPadding(0, dpToPx(16), 0, 0)
            })
        }
    }

    private fun hideLoadingView() {
        loadingView.visibility = View.GONE
    }

    override fun onPause() {
        super.onPause()
        CookieManager.getInstance().flush()
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        webView.saveState(outState)
    }

    private data class TabItem(val iconResId: Int, val title: String, val id: String)

    companion object {
        const val EXTRA_OPEN_TAB = "com.meras.userapp.OPEN_TAB"
        private const val PREFS_NAME = "meras_user_app"
        private const val KEY_FCM_TOKEN = "fcm_token"

        fun saveFcmToken(context: Context, token: String) {
            context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
                .edit()
                .putString(KEY_FCM_TOKEN, token)
                .apply()
        }
    }
}
