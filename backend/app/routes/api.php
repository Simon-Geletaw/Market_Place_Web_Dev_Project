<?php

declare(strict_types=1);

/**
 * API Route Registry
 *
 * Format per route:
 *   'method'     => HTTP verb (GET, POST, PATCH, DELETE)
 *   'path'       => URL path (supports {param} segments)
 *   'controller' => Controller class name
 *   'action'     => Method name on the controller
 *   'middleware' => Ordered list: 'auth' | 'role:<roleName>'
 *
 * Role names must match the ROLE enum values (case-insensitive compare in role.php):
 *   customer | provider | admin
 */
return [

    // ----------------------------------------------------------------
    // System health
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/health',
        'controller' => StatusController::class,
        'action'     => 'ok',
        'middleware' => [],
    ],

    // ----------------------------------------------------------------
    // Authentication  (no auth required — guest endpoints)
    // ----------------------------------------------------------------
    [
        'method'     => 'POST',
        'path'       => '/api/auth/login',
        'controller' => AuthController::class,
        'action'     => 'login',
        'middleware' => ['validate:auth_login'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/auth/register',
        'controller' => AuthController::class,
        'action'     => 'register',
        'middleware' => ['validate:auth_register'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/auth/logout',
        'controller' => AuthController::class,
        'action'     => 'logout',
        'middleware' => ['auth'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/auth/me',
        'controller' => AuthController::class,
        'action'     => 'me',
        'middleware' => [], // me() handles its own 401 gracefully
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/auth/forgot-password',
        'controller' => AuthController::class,
        'action'     => 'forgotPassword',
        'middleware' => ['validate:forgot_password'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/auth/reset-password',
        'controller' => AuthController::class,
        'action'     => 'resetPassword',
        'middleware' => ['validate:reset_password'],
    ],
    [
        'method'     => 'PUT',
        'path'       => '/api/auth/profile',
        'controller' => AuthController::class,
        'action'     => 'updateProfile',
        'middleware' => ['auth', 'validate:profile_update'],
    ],
    [
        'method'     => 'PUT',
        'path'       => '/api/auth/password',
        'controller' => AuthController::class,
        'action'     => 'updatePassword',
        'middleware' => ['auth', 'validate:password_update'],
    ],

    // ----------------------------------------------------------------
    // Public categories  (dynamic list for request forms)
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/categories',
        'controller' => AdminController::class,
        'action'     => 'categories',
        'middleware' => [],
    ],

    // ----------------------------------------------------------------
    // Marketplace (provider browse)
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/marketplace',
        'controller' => RequestController::class,
        'action'     => 'browse',
        'middleware' => ['auth', 'role:provider'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/marketplace/requests',
        'controller' => RequestController::class,
        'action'     => 'browse',
        'middleware' => ['auth', 'role:provider'],
    ],

    // ----------------------------------------------------------------
    // Service Requests — Customer
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/requests',
        'controller' => RequestController::class,
        'action'     => 'myRequests',
        'middleware' => ['auth', 'role:customer'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/requests',
        'controller' => RequestController::class,
        'action'     => 'create',
        'middleware' => ['auth', 'role:customer', 'validate:request_create'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/requests/{id}',
        'controller' => RequestController::class,
        'action'     => 'show',
        'middleware' => ['auth'],
    ],

    // ----------------------------------------------------------------
    // Offers on a specific request
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/requests/{id}/offers',
        'controller' => OfferController::class,
        'action'     => 'listForRequest',
        'middleware' => ['auth', 'role:customer'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/requests/{id}/offers',
        'controller' => OfferController::class,
        'action'     => 'submit',
        'middleware' => ['auth', 'role:provider', 'validate:offer_submit'],
    ],

    // ----------------------------------------------------------------
    // Request lifecycle — Provider marks complete
    // ----------------------------------------------------------------
    [
        'method'     => 'POST',
        'path'       => '/api/requests/{id}/complete',
        'controller' => RequestController::class,
        'action'     => 'markCompleted',
        'middleware' => ['auth', 'role:provider', 'validate:request_complete'],
    ],

    // ----------------------------------------------------------------
    // Review — Customer submits after completion
    // ----------------------------------------------------------------
    [
        'method'     => 'POST',
        'path'       => '/api/requests/{id}/review',
        'controller' => ReviewController::class,
        'action'     => 'submitForRequest',
        'middleware' => ['auth', 'role:customer', 'validate:review_submit'],
    ],

    // ----------------------------------------------------------------
    // Offers — Provider views own offers
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/offers',
        'controller' => OfferController::class,
        'action'     => 'myOffers',
        'middleware' => ['auth', 'role:provider'],
    ],
    [
        'method'     => 'PATCH',
        'path'       => '/api/offers/{id}/accept',
        'controller' => OfferController::class,
        'action'     => 'accept',
        'middleware' => ['auth', 'role:customer', 'validate:offer_accept'],
    ],
    [
        'method'     => 'PATCH',
        'path'       => '/api/offers/{id}/reject',
        'controller' => OfferController::class,
        'action'     => 'reject',
        'middleware' => ['auth', 'role:customer', 'validate:offer_reject'],
    ],
    [
        'method'     => 'PATCH',
        'path'       => '/api/offers/{id}/counter',
        'controller' => OfferController::class,
        'action'     => 'counter',
        'middleware' => ['auth', 'role:customer', 'validate:offer_counter'],
    ],

    // ----------------------------------------------------------------
    // Reviews — Public provider profile + customer history
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/reviews/my',
        'controller' => ReviewController::class,
        'action'     => 'myReviews',
        'middleware' => ['auth', 'role:customer'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/reviews/given',
        'controller' => ReviewController::class,
        'action'     => 'given',
        'middleware' => ['auth', 'role:customer'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/reviews/received',
        'controller' => ReviewController::class,
        'action'     => 'received',
        'middleware' => ['auth', 'role:provider'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/reviews/provider/{id}',
        'controller' => ReviewController::class,
        'action'     => 'providerReviews',
        'middleware' => [],
    ],

    // ----------------------------------------------------------------
    // Dashboards
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/customer/dashboard',
        'controller' => DashboardController::class,
        'action'     => 'customer',
        'middleware' => ['auth', 'role:customer'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/provider/dashboard',
        'controller' => DashboardController::class,
        'action'     => 'provider',
        'middleware' => ['auth', 'role:provider'],
    ],

    // ----------------------------------------------------------------
    // Provider — assigned and completed job lists
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/provider/jobs/assigned',
        'controller' => RequestController::class,
        'action'     => 'providerAssignedJobs',
        'middleware' => ['auth', 'role:provider'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/provider/jobs/completed',
        'controller' => RequestController::class,
        'action'     => 'providerCompletedJobs',
        'middleware' => ['auth', 'role:provider'],
    ],

    // ----------------------------------------------------------------
    // Notifications
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/notifications',
        'controller' => NotificationController::class,
        'action'     => 'index',
        'middleware' => ['auth'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/notifications/{id}/read',
        'controller' => NotificationController::class,
        'action'     => 'markRead',
        'middleware' => ['auth'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/notifications/read-all',
        'controller' => NotificationController::class,
        'action'     => 'readAll',
        'middleware' => ['auth'],
    ],

    // ----------------------------------------------------------------
    // Admin
    // ----------------------------------------------------------------
    [
        'method'     => 'GET',
        'path'       => '/api/admin/dashboard',
        'controller' => AdminController::class,
        'action'     => 'dashboard',
        'middleware' => ['auth', 'role:admin'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/admin/metrics',
        'controller' => AdminController::class,
        'action'     => 'metrics',
        'middleware' => ['auth', 'role:admin'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/admin/audit-logs',
        'controller' => AdminController::class,
        'action'     => 'auditLogs',
        'middleware' => ['auth', 'role:admin'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/admin/providers',
        'controller' => AdminController::class,
        'action'     => 'providers',
        'middleware' => ['auth', 'role:admin'],
    ],
    [
        'method'     => 'PATCH',
        'path'       => '/api/admin/providers/{id}/verify',
        'controller' => AdminController::class,
        'action'     => 'verifyProvider',
        'middleware' => ['auth', 'role:admin'],
    ],
    [
        'method'     => 'GET',
        'path'       => '/api/admin/categories',
        'controller' => AdminController::class,
        'action'     => 'categories',
        'middleware' => ['auth', 'role:admin'],
    ],
    [
        'method'     => 'POST',
        'path'       => '/api/admin/categories',
        'controller' => AdminController::class,
        'action'     => 'createCategory',
        'middleware' => ['auth', 'role:admin', 'validate:category_create'],
    ],
    [
        'method'     => 'PATCH',
        'path'       => '/api/admin/categories/{id}',
        'controller' => AdminController::class,
        'action'     => 'updateCategory',
        'middleware' => ['auth', 'role:admin', 'validate:category_update'],
    ],
];
