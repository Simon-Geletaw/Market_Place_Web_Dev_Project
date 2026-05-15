<?php

declare(strict_types=1);

final class NotificationService
{
    private NotificationRepository $notifications;

    public function __construct(NotificationRepository $notifications)
    {
        $this->notifications = $notifications;
    }

    public function notify(
        string $userId,
        string $message,
        string $type = 'general',
        ?string $link = null,
        ?string $entityType = null,
        ?string $entityId = null
    ): void {
        if ($userId === '') {
            return;
        }

        try {
            $this->notifications->create($userId, $message, $type, $link, $entityType, $entityId);
        } catch (Throwable $exception) {
            error_log('[NotificationService] Failed to create notification: ' . $exception->getMessage());
        }
    }

    public function listForUser(string $userId): array
    {
        return array_map([$this, 'normalize'], $this->notifications->findByUser($userId));
    }

    public function markRead(string $notificationId, string $userId): array
    {
        $updated = $this->notifications->markRead($notificationId, $userId);

        return ['success' => $updated, 'message' => $updated ? 'Notification marked as read.' : 'Notification not found.'];
    }

    public function markAllRead(string $userId): array
    {
        return ['success' => true, 'count' => $this->notifications->markAllRead($userId)];
    }

    private function normalize(array $row): array
    {
        return [
            'id' => $row['NOTIFICATION_ID'],
            'user_id' => $row['USER_ID'],
            'message' => $row['MESSAGE'],
            'type' => $row['TYPE'] ?? 'general',
            'link' => $row['LINK'] ?? null,
            'entity_type' => $row['ENTITY_TYPE'] ?? null,
            'entity_id' => $row['ENTITY_ID'] ?? null,
            'is_read' => (bool) ($row['IS_READ'] ?? false),
            'created_at' => $row['CREATED_AT'],
        ];
    }
}
