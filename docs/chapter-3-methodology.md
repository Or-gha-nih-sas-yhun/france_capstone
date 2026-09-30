# CHAPTER III

# METHODOLOGY

## Research Design

This chapter presents the methodology used in developing the **Mera's General Merchandise Store Management System**. It explains how the project requirements were identified, analysed, designed, implemented, tested, and reviewed. The system was developed to centralize the store's product inventory, point-of-sale transactions, sales reporting, customer inquiries, and support communication while making selected customer services accessible through a web and Android mobile interface.

The study used the **Rapid Application Development (RAD)** model. RAD was selected because it supports the rapid delivery of working prototypes and repeated improvement through user feedback. This approach was appropriate for the project because store administrators and customers have different needs: administrators require accurate stock, sales, reports, and account controls, while customers require a clear product catalogue, inquiry facilities, account access, and timely support.

The RAD process used requirements planning, user-oriented design, rapid construction, testing, and deployment activities. Working modules were reviewed as they became available, allowing the researchers to refine the user interface and system behaviour based on feedback before final deployment.

## Development Methodology: Rapid Application Development (RAD)

The development process followed six related phases based on the RAD model. It began with identifying the store's operational needs and ended with system review, user preparation, and deployment.

**Figure 1. Rapid Application Development (RAD) Lifecycle Model**

### Phase 1: Requirements Planning

During this phase, the researchers identified the problems encountered in managing merchandise, recording sales, responding to customer concerns, and monitoring store activity. The existing workflow and the requirements of the intended users were reviewed to establish the scope of the proposed system.

The following activities were carried out:

- Identified the need for a centralized product catalogue containing product name, stock quantity, unit, category, price, and optional bulk-pricing information.
- Defined the point-of-sale workflow for adding products to a cart, validating available stock, recording cash or GCash payments, computing change, reducing stock after checkout, and producing printable receipts.
- Identified reporting requirements for daily, weekly, monthly, yearly, and all-time sales summaries, top-selling products, transaction history, and CSV export.
- Defined customer-facing functions, including product browsing, customer registration and login, inquiry submission, inquiry-status viewing, support chat, and chatbot assistance.
- Identified administrative functions for inventory management, product CSV import, inquiry response, user-account and role management, sales monitoring, activity logs, settings, and data export.
- Prepared a prioritized list of requirements covering authentication, inventory, point-of-sale, reporting, customer support, and mobile accessibility.

At the end of this phase, the researchers established the system objectives, user roles, required data, and prioritized modules for development.

### Phase 2: Analysis

The analysis phase examined how each required function would operate within one connected system. The researchers mapped the movement of information from product creation to sale recording and report generation, and from customer inquiry submission to an administrator's response and notification.

The activities in this phase included the following:

- Analysed the current inventory and sales-recording process to identify risks such as inaccurate stock counts, slow retrieval of records, and difficulty in producing sales summaries.
- Defined the system users as administrators and customers. Role-based access control was specified so that only authorized administrators could access inventory, point-of-sale, reporting, user-management, and activity-log functions.
- Developed user stories, such as: *As an administrator, I want to record a sale and automatically update stock so that inventory remains accurate*; and *As a customer, I want to submit an inquiry and receive a response so that I can obtain assistance without visiting the store.*
- Identified the major data entities: users, products, sales, sale items, inquiries, messages, and activity logs.
- Specified data-validation rules for product quantities and prices, login credentials, payment input, CSV imports, inquiries, and administrator-only actions.
- Prioritized modules according to operational importance, beginning with authentication, inventory, and point-of-sale functions before reports, support tools, and mobile integration.

### Phase 3: Design

In the design phase, the researchers transformed the requirements into page layouts, workflows, database structures, and system diagrams. Prototypes and interfaces were reviewed and refined so that product information, sales data, and customer-support actions would remain easy to understand and use.

The design activities included:

- Designed public and customer pages for the product catalogue, product search and filtering, inquiry form, account profile, notifications, chatbot, and live-support chat.
- Designed the administrator dashboard to present daily, weekly, monthly, and total revenue; total products; low-stock items; recent sales; sales trends; top products; and stock by category.
- Designed the inventory interface for creating, updating, searching, importing, and monitoring products, including units and bulk-pricing rules.
- Designed the point-of-sale interface for product selection, cart updates, payment-method selection, checkout, receipt display, and stock deduction.
- Designed the sales-report interface for period-based summaries, product performance, transaction records, and downloadable CSV reports.
- Created the database design and Entity-Relationship Diagram (ERD) for users, products, sales, sale items, inquiries, messages, and activity logs.
- Prepared context-flow, data-flow, use-case, and detailed-flowchart diagrams to describe the interaction between customers, administrators, and the system.
- Designed authentication, password-reset, role-checking, activity-logging, email-response, and push-notification flows to protect accounts and support traceable store operations.

The project diagrams prepared for this phase are available in the following source files and should be exported as figures for the final manuscript:

- `docs/meras-use-case-diagram.drawio`
- `docs/meras-dfd.drawio`
- `docs/meras-detailed-flowcharts.drawio`
- `docs/meras-erd.drawio`

### Phase 4: Testing

Testing was performed throughout development to verify that each module functioned correctly before it was connected to the rest of the system. The testing process focused on correct data handling, secure role restrictions, accurate stock adjustments, and understandable user interactions.

The testing approach included the following levels:

**Unit Testing.** Individual functions were tested separately. This included product validation, stock updates, bulk-price calculations, cart updates, cash-change computation, CSV-column validation, inquiry submission, account validation, and chatbot response handling.

**Integration Testing.** Related modules were tested together. Examples included verifying that a completed point-of-sale transaction created a sale record and sale items, reduced the corresponding product quantities, appeared on the dashboard and reports, and produced a printable receipt. The inquiry workflow was also checked to ensure that a customer inquiry could be stored, answered by an administrator, and made visible to the customer through the profile and notification pages.

**System Testing.** The complete application was tested from public catalogue access through administrator operations. The researchers checked page navigation, authentication, role-based route protection, inventory management, point-of-sale transactions, reports, inquiry handling, chat, activity logs, email notifications, and mobile-web access.

**User Acceptance Testing.** Store administrators and selected customers should evaluate the completed system using realistic tasks, such as adding a product, completing a sale, locating a report, submitting an inquiry, and checking a response. Their feedback should be recorded to identify usability issues and final improvements before full implementation.

### Phase 5: Implementation

The implementation phase involved translating the approved designs into working modules. The modules were developed in sequence so that each completed feature could be reviewed before the next feature was developed.

**Stage 1 – Core System and Authentication.** The development environment, database, registration, login, password reset, customer profile, and administrator role restrictions were implemented. Activity logging was added to record relevant administrator, customer, and guest actions.

**Stage 2 – Inventory Management.** The product module was implemented with create, read, update, and delete operations. It supports product names, SKUs, categories, units, prices, quantities, optional bulk pricing, low-stock monitoring, search, pagination, and secure CSV import.

**Stage 3 – Point-of-Sale and Sales Records.** The POS module was developed to manage carts, quantity changes, cash and GCash payment options, checkout validation, receipt generation, sale records, sale-item records, and automatic stock deduction.

**Stage 4 – Dashboard and Reports.** The administrator dashboard and reporting features were implemented. These functions present sales totals, low-stock alerts, recent transactions, sales trends, top-selling products, stock distribution, time-based report filters, and CSV export.

**Stage 5 – Customer Support and Account Services.** The public catalogue, inquiry form, inquiry-management functions, email responses, notification view, live chat, and keyword-based support chatbot were implemented. The chatbot can provide store information and product-availability guidance, and it can direct customers to live Messenger support when needed.

**Stage 6 – User Management, Mobile Access, and Final Integration.** User and role management, settings, data export, activity-log monitoring, Firebase Cloud Messaging support, and the Kotlin Android application were integrated. The Android application provides customers with a mobile WebView route to the customer-facing system and can receive inquiry-response notifications when configured.

After each stage, completed features were reviewed and refined before the next module was developed.

### Phase 6: Review and Deployment

The final phase focused on preparing the system for practical store use. The completed web application was reviewed to confirm that essential features were available, accessible, and consistent with the approved requirements.

The following activities were included:

- Deployed the Laravel web application to an environment accessible through a browser.
- Prepared the Android application for customer access to the `/user-app` route.
- Reviewed administrator access to the dashboard, inventory, POS, reports, inquiries, users, logs, and settings.
- Verified the customer workflow for browsing products, registering or logging in, submitting inquiries, viewing responses, and using the support chat.
- Prepared user guidance for product maintenance, checkout, report export, inquiry response, account management, and basic troubleshooting.
- Collected feedback from intended users and recorded improvement items for future releases.

## Summary of RAD Activities

| RAD Activity | Application in the Project |
| --- | --- |
| Requirements Planning | System functions and data requirements were identified and prioritised before development. |
| User Design | Interfaces, workflows, database structures, and diagrams were prepared and reviewed. |
| Rapid Construction | The system was built module by module, beginning with core store operations. |
| Prototype Review | Working modules were reviewed to identify needed adjustments before subsequent development. |
| Testing | Functions were tested during development and again after integration. |
| Cutover | The completed system was prepared for deployment, user guidance, and final evaluation. |

## Tools and Technologies

The following tools and technologies were used to build the system:

- **Programming Languages:** PHP, HTML, CSS, JavaScript, and Kotlin
- **Backend Framework:** Laravel 10 running on PHP 8.1 or later
- **Frontend:** Laravel Blade templates with responsive HTML, CSS, and JavaScript
- **Database:** MySQL
- **Local Development Environment:** Laragon
- **Mobile Development:** Android Studio and Kotlin using a WebView customer application
- **Notifications:** Firebase Cloud Messaging for mobile inquiry-response notifications
- **Email:** SMTP-based Laravel mail notifications for inquiry responses and password resets
- **Diagrams:** Draw.io for the use-case diagram, data-flow diagrams, detailed flowcharts, and ERD
- **Version Control:** Git
- **Testing:** Laravel/PHPUnit tests and manual browser-based functional testing

## Evaluation Method

The system should be evaluated by store administrators and selected customers using a structured questionnaire based on the ISO/IEC 25010 Software Quality Model. The evaluation should measure whether the system meets operational needs and can be used effectively by its intended users.

The questionnaire should assess the following quality characteristics:

- **Functional Suitability:** whether inventory, sales, reporting, inquiry, and account functions produce the required results.
- **Usability:** whether users can understand the navigation, product information, POS workflow, reports, and support features with minimal assistance.
- **Reliability:** whether records, stock quantities, sale transactions, inquiries, and notifications remain consistent during normal use.
- **Performance Efficiency:** whether pages, reports, searches, and transactions respond within an acceptable time.
- **Security:** whether authentication, password protection, role-based administrator access, and input validation protect system data from unauthorized use.
- **Compatibility:** whether the customer-facing interface works appropriately in supported web browsers and in the Android WebView application.

A five-point Likert scale may be used for each item: 5 – Strongly Agree, 4 – Agree, 3 – Neutral, 2 – Disagree, and 1 – Strongly Disagree. The responses may be summarized using the weighted mean and standard deviation to determine the overall level of quality and identify areas for improvement.

## Schedule Feasibility

A Gantt chart should be included in the final manuscript to present the project schedule from requirements gathering through analysis, design, implementation, testing, deployment, and evaluation.

**Figure 2. Gantt Chart of the Mera's General Merchandise Store Management System Development**

## Requirements Modelling

### Context Flow Diagram

A context-flow diagram presents the system at a high level and identifies its interaction with external entities. In this project, customers provide registration details, inquiries, chat messages, and mobile access requests; administrators manage products, sales, reports, customer responses, users, and settings; and the system returns catalogue information, receipts, reports, responses, notifications, and audit records.

**Figure 3. Context Flow Diagram**

### Data Flow Diagram

The data-flow diagram shows how information moves through the system. The major processes include customer access and support, product and inventory management, point-of-sale processing, sales reporting, user and role management, and activity logging. The diagram should show how data is stored in the users, products, sales, sale items, inquiries, messages, and activity-log records.

**Figure 4. Level 1 Data Flow Diagram**

**Figure 5. Level 2 Data Flow Diagram – Customer Support and Inquiry Process**

**Figure 6. Level 2 Data Flow Diagram – Inventory Management Process**

**Figure 7. Level 2 Data Flow Diagram – Point-of-Sale Process**

### System Architecture

The system uses a client-server architecture. Customers and administrators access the application through web browsers, while customers may also use the Android WebView application. Requests are processed by the Laravel application, where authentication, role checks, validation, inventory handling, point-of-sale transactions, reporting, inquiry management, chatbot responses, and activity logging are performed. Persistent operational data is stored in MySQL. SMTP mail and Firebase Cloud Messaging are used when configured to deliver inquiry-response notifications. This architecture enables the store to maintain a centralized and traceable record of products, sales, customer concerns, and administrator actions.

**Figure 8. System Architecture of the Mera's General Merchandise Store Management System**

### Entity-Relationship Diagram

The Entity-Relationship Diagram represents the relationships among the core records of the system. A user can create sales and submit inquiries. Each sale contains one or more sale items, and each sale item is associated with a product. Products provide the catalogue and inventory data used by the point-of-sale process. Inquiries record customer concerns and, when answered, are linked to the administrator who responded. Messages and activity logs retain support and audit information associated with system use.

**Figure 9. Entity-Relationship Diagram**
