<?php
/**
 * Contrôleur Feedback
 * Reçoit les soumissions du FAB feedback et les enregistre en base.
 */
class FeedbackController extends Controller
{
    public function submit(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $type = trim($_POST['feedback_type'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!in_array($type, ['problem', 'idea'], true)) {
            $type = 'problem';
        }
        if ($message === '') {
            $this->json(['ok' => false, 'error' => 'Message requis'], 400);
            return;
        }

        require_once dirname(__DIR__, 2) . '/gestion/config.php';

        $data = [
            'type'    => $type,
            'message' => $message,
            'source'  => 'app',
        ];
        if (!empty($_SESSION['user_email'])) {
            $data['user_email'] = $_SESSION['user_email'];
        }
        if (!empty($_SESSION['user_name'])) {
            $data['user_name'] = $_SESSION['user_name'];
        }
        if (!empty($_SESSION['user_id'])) {
            $data['platform_user_id'] = (int) $_SESSION['user_id'];
        }

        if (Feedback::create($data)) {
            $this->json(['ok' => true]);
        } else {
            $this->json(['ok' => false, 'error' => 'Erreur lors de l\'enregistrement'], 500);
        }
    }
}
