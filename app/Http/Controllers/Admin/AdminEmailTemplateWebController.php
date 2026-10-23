<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EmailType;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\EmailTemplate;
use App\Services\Email\EmailService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminEmailTemplateWebController extends Controller
{
    public function __construct(
        protected EmailService $emailService,
    ) {}

    /**
     * List all editable email templates.
     */
    public function index(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_VIEW)) {
            abort(403, 'You do not have permission to view email templates.');
        }

        $this->emailService->ensureDefaultTemplates();

        $templates = EmailTemplate::all()->keyBy(fn($t) => $t->type->value);

        $items = [];
        foreach (EmailService::DEFAULT_TEMPLATES as $typeName => $def) {
            $template = $templates->get($typeName);
            $items[] = [
                'type' => $typeName,
                'name' => str_replace('_', ' ', ucwords(strtolower($typeName), '_')),
                'description' => $def['description'],
                'default_subject' => $def['subject'],
                'subject' => $template?->subject ?? $def['subject'],
                'heading' => $template?->heading ?? $def['heading'],
                'intro' => $template?->intro ?? $def['intro'],
                'has_custom_html' => !empty($template?->body_html),
                'show_item_images' => $template ? (bool) $template->show_item_images : true,
                'updated_at' => $template?->updated_at,
            ];
        }

        return view('admin.emails.index', [
            'templates' => $items,
            'canEdit' => $admin->hasPermission(Permissions::SETTINGS_UPDATE),
        ]);
    }

    /**
     * Show editor for an email template.
     */
    public function edit(string $type): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_VIEW)) {
            abort(403, 'You do not have permission to view email templates.');
        }

        $this->emailService->ensureDefaultTemplates();

        $typeUpper = strtoupper($type);
        if (!isset(EmailService::DEFAULT_TEMPLATES[$typeUpper])) {
            abort(404, 'Email template not found.');
        }

        $typeEnum = EmailType::from($typeUpper);
        $template = EmailTemplate::firstOrCreate(
            ['type' => $typeEnum],
            [
                'subject' => EmailService::DEFAULT_TEMPLATES[$typeUpper]['subject'],
                'heading' => EmailService::DEFAULT_TEMPLATES[$typeUpper]['heading'],
                'intro' => EmailService::DEFAULT_TEMPLATES[$typeUpper]['intro'],
                'body_html' => null,
                'show_item_images' => true,
                'version' => 1,
            ]
        );

        // Render preview
        $preview = $this->emailService->preview($typeUpper);

        return view('admin.emails.edit', [
            'template' => $template,
            'typeName' => $typeUpper,
            'definition' => EmailService::DEFAULT_TEMPLATES[$typeUpper],
            'preview' => $preview,
            'canEdit' => $admin->hasPermission(Permissions::SETTINGS_UPDATE),
            'adminEmail' => $admin->email,
        ]);
    }

    /**
     * Update an email template.
     */
    public function update(Request $request, string $type): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE)) {
            abort(403, 'You do not have permission to update email templates.');
        }

        $typeUpper = strtoupper($type);
        if (!isset(EmailService::DEFAULT_TEMPLATES[$typeUpper])) {
            abort(404, 'Email template not found.');
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'heading' => ['required', 'string', 'max:255'],
            'intro' => ['required', 'string', 'max:2000'],
            'body_html' => ['nullable', 'string', 'max:50000'],
            'show_item_images' => ['nullable', 'boolean'],
        ]);

        $typeEnum = EmailType::from($typeUpper);
        $template = EmailTemplate::where('type', $typeEnum)->firstOrFail();

        $template->update([
            'subject' => trim($validated['subject']),
            'heading' => trim($validated['heading']),
            'intro' => trim($validated['intro']),
            'body_html' => !empty($validated['body_html']) ? trim($validated['body_html']) : null,
            'show_item_images' => $request->has('show_item_images'),
            'version' => ($template->version ?? 1) + 1,
        ]);

        return redirect('/admin/emails/' . $typeUpper)->with('success', 'Email template updated successfully.');
    }

    /**
     * Live preview AJAX endpoint.
     */
    public function preview(Request $request, string $type): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_VIEW)) {
            abort(403, 'Unauthorized');
        }

        $typeUpper = strtoupper($type);
        if (!isset(EmailService::DEFAULT_TEMPLATES[$typeUpper])) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $preview = $this->emailService->preview($typeUpper, [
            'subject' => $request->input('subject'),
            'heading' => $request->input('heading'),
            'intro' => $request->input('intro'),
            'body_html' => $request->input('body_html'),
            'show_item_images' => $request->boolean('show_item_images', true),
        ]);

        return response()->json([
            'success' => true,
            'subject' => $preview['subject'],
            'html' => $preview['html'],
            'text' => $preview['text'],
        ]);
    }

    /**
     * Send test email to specified address.
     */
    public function sendTest(Request $request, string $type): JsonResponse|RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE)) {
            abort(403, 'Unauthorized');
        }

        $typeUpper = strtoupper($type);
        $recipient = trim((string) $request->input('recipient', $admin->email));

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please provide a valid email address.'], 422);
            }
            return back()->with('error', 'Please provide a valid email address.');
        }

        $sent = $this->emailService->sendTestEmail($typeUpper, $recipient);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $sent,
                'message' => $sent
                    ? "Test email sent successfully to {$recipient}!"
                    : "Failed to send test email. Please check your SMTP settings in .env",
            ]);
        }

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? "Test email sent to {$recipient}!" : "Failed to send test email. Check SMTP settings."
        );
    }

    /**
     * Reset email template back to factory defaults.
     */
    public function reset(string $type): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE)) {
            abort(403, 'Unauthorized');
        }

        $typeUpper = strtoupper($type);
        if (!isset(EmailService::DEFAULT_TEMPLATES[$typeUpper])) {
            abort(404, 'Template not found.');
        }

        $def = EmailService::DEFAULT_TEMPLATES[$typeUpper];
        $typeEnum = EmailType::from($typeUpper);

        EmailTemplate::updateOrCreate(
            ['type' => $typeEnum],
            [
                'subject' => $def['subject'],
                'heading' => $def['heading'],
                'intro' => $def['intro'],
                'body_html' => null,
                'show_item_images' => true,
                'version' => 1,
            ]
        );

        return redirect('/admin/emails/' . $typeUpper)->with('success', 'Email template reset to default words and layout.');
    }
}
