<?php

declare(strict_types=1);

final class NotificationController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $db = (new DatabaseConnector())->getConnection();
        $this->notificationService = new NotificationService(new NotificationRepository($db));
    }

    public function index(): array
    {
        return success_response('Notifications retrieved.', $this->notificationService->listForUser(current_user_id()));
    }

    public function markRead(): array
    {
        $result = $this->notificationService->markRead(route_param('id', ''), current_user_id());

        if (!$result['success']) {
            return error_response($result['message'], [], 404);
        }

        return success_response($result['message']);
    }

    public function readAll(): array
    {
        $result = $this->notificationService->markAllRead(current_user_id());

        return success_response('Notifications marked as read.', ['updated' => $result['count']]);
    }
}
