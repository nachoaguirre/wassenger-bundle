# Wassenger Symfony Bundle

[![Latest Version](https://img.shields.io/github/v/release/nachoaguirre/wassenger-bundle?display_name=tag)](https://github.com/nachoaguirre/wassenger-bundle)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Symfony Compatibility](https://img.shields.io/badge/symfony-6.4%20%2F%207.x%20%2F%208.x-blue)](https://symfony.com)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.4-purple)](https://php.net)

## What is it?

The **Wassenger Symfony Bundle** provides a seamless, modern, and event-driven integration between your Symfony application and the [Wassenger WhatsApp API](https://wassenger.com/). 

Built with clean architecture principles in mind, it abstracts the complexity of raw HTTP requests and provides a developer-friendly interface to send messages, validate phone numbers, and build interactive WhatsApp bots using native Symfony Events.

## How it works

This bundle is built around three core pillars:
1. **The Provider:** A service that acts as a bridge to the Wassenger API, allowing you to dispatch text, media, scheduled or template messages to individuals and groups effortlessly.
2. **The Recipient Registry:** A configuration-driven system that lets you define stable aliases (e.g., `support_group`, `billing`) mapping to actual phone numbers or WhatsApp Group IDs.
3. **The Webhook Event Dispatcher:** A secure, out-of-the-box controller that receives incoming messages from Wassenger and converts them into Symfony `WebhookEvent` objects. Your application simply listens to these events to trigger business logic.

---

## Requirements

* **PHP:** >= 8.4
* **Symfony:** 6.4 (LTS), 7.x or 8.x
* **Wassenger Account:** An active API Key and Device ID from Wassenger.

---

## Installation

Install the bundle using Composer:

```bash
composer require nachoaguirre/wassenger-bundle
```

If you are not using Symfony Flex, enable the bundle manually in your `config/bundles.php`:

```php
return [
    // ...
    Nachoaguirre\WassengerBundle\NachoaguirreWassengerBundle::class => ['all' => true],
];
```

---

## Configuration

Set up your environment variables in your `.env` file:

```env
WASSENGER_API_KEY=your_api_key_here
WASSENGER_DEVICE_ID=your_device_id_here
WASSENGER_WEBHOOK_SECRET=optional_secure_token
```

Create a configuration file at `config/packages/nachoaguirre_wassenger.yaml` to define your channels and global settings:

```yaml
nachoaguirre_wassenger:
    enable_greetings: true # Toggles built-in multilingual auto-replies for "hello"
    webhook_secret: '%env(WASSENGER_WEBHOOK_SECRET)%'
    
    providers:
        wassenger:
            api_key: '%env(WASSENGER_API_KEY)%'
            device_id: '%env(WASSENGER_DEVICE_ID)%'
            
    # Define your static recipients (individuals or groups)
    recipients:
        support_team:
            identifier: '1203630234567890@g.us'
            type: 'group'
            enabled: true
        marketing:
            identifier: '+1234567890'
            type: 'individual'
            enabled: false # Easily pause notifications without changing code
```

---

## Examples

### 1. Sending a Basic Message
Inject the `WassengerProvider` and `RecipientRegistry` into your services or controllers to send a message.

```php
namespace App\Controller;

use Nachoaguirre\WassengerBundle\Provider\WassengerProvider;
use Nachoaguirre\WassengerBundle\Registry\RecipientRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController
{
    #[Route('/notify', name: 'app_notify')]
    public function notify(WassengerProvider $whatsapp, RecipientRegistry $registry): Response 
    {
        // Using an alias defined in YAML
        $support = $registry->getActive('support_team');
        if ($support) {
            $whatsapp->sendMessage($support->identifier, "🚨 System alert: CPU usage is high!");
        }

        // Or sending directly to a dynamic phone number 
        // (The bundle automatically normalizes E164 formats, stripping spaces/dashes)
        $sent = $whatsapp->sendMessage('+56 9 1234 5678', "Your order has been shipped.");

        // Every send returns a SentMessage DTO with the Wassenger message ID,
        // useful to correlate delivery-status webhooks later on.
        // $sent->id, $sent->status, $sent->deliverAt, $sent->raw

        return new Response('Notifications sent. Message ID: ' . $sent->id);
    }
}
```

### 2. Sending Media (Images, Videos, Documents)
Send any publicly reachable file by URL, with an optional caption. Wassenger supports images (JPEG, PNG, WEBP), videos (MP4), audio (MP3, OGG), GIFs and documents (PDF, DOCX, ZIP, etc.).

```php
$whatsapp->sendMedia(
    '+56912345678',
    'https://example.com/invoices/inv-2026-001.pdf',
    caption: 'Here is your invoice 🧾'
);
```

### 3. Scheduling a Message
Deliver a message at a future date. The bundle sends the timestamp in ISO 8601 (`deliverAt`), as expected by the Wassenger API.

```php
$whatsapp->scheduleMessage(
    '+56912345678',
    '¡Feliz Navidad! 🎄',
    new \DateTimeImmutable('2026-12-24 20:00:00', new \DateTimeZone('America/Santiago'))
);
```

### 4. Sending to WhatsApp Groups
Pass a group ID (ending in `@g.us`) as the recipient — the bundle detects it automatically and addresses the group instead of a phone number. Group IDs also work through the Recipient Registry, as shown in the configuration above.

```php
$whatsapp->sendMessage('1203630234567890@g.us', 'Deploy finished successfully ✅');
$whatsapp->sendMedia('1203630234567890@g.us', 'https://example.com/report.pdf', 'Weekly report');
```

### 5. Using Doctrine Entities as Recipients
You can bind your existing database entities (like `User` or `Customer`) to the bundle by implementing the `WhatsappRecipientInterface`.

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nachoaguirre\WassengerBundle\Contract\WhatsappRecipientInterface;

#[ORM\Entity]
class User implements WhatsappRecipientInterface
{
    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    public function getWhatsappIdentifier(): string
    {
        return $this->phone; 
    }
}
```
Now you can pass the object directly to your notification logic, making your code incredibly clean.

### 6. Validating a Number
Wassenger provides an endpoint to check if a number actually has an active WhatsApp account. The bundle wraps this into a convenient DTO.

```php
$validation = $whatsapp->validateNumber('+56912345678');

if ($validation->exists) {
    echo "This is a valid WhatsApp account in " . $validation->countryData['name']; 
} else {
    echo "Invalid number: " . $validation->errorMessage;
}
```

---

## Building a WhatsApp Bot (Webhooks)

The bundle exposes a secure webhook endpoint out of the box. To handle incoming messages and build interactive bots:

### Step 1: Import the Routing
Add the bundle's routes to your `config/routes/nachoaguirre_wassenger.yaml`:

```yaml
nachoaguirre_wassenger:
    resource: '@NachoaguirreWassengerBundle/config/routes.yaml'
```
*Configure Wassenger's dashboard to point to: `https://yourdomain.com/webhook/wassenger`*

### Step 2: Create an Event Subscriber
Listen to the `WebhookEvent` in your application to process incoming text and run business logic.

```php
namespace App\EventSubscriber;

use Nachoaguirre\WassengerBundle\Event\WebhookEvent;
use Nachoaguirre\WassengerBundle\Provider\WassengerProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChatBotSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WassengerProvider $whatsapp
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            WebhookEvent::NAME => 'onMessageReceived',
        ];
    }

    public function onMessageReceived(WebhookEvent $event): void
    {
        if ($event->getEventType() !== 'message:in') {
            return; // Ignore read receipts, delivery statuses, etc.
        }

        $payload = $event->getPayload();
        $text = strtolower(trim($payload['data']['body'] ?? ''));
        $sender = $payload['data']['from'];

        match ($text) {
            'status' => $this->whatsapp->sendMessage($sender, "All systems operational! 🟢"),
            'help' => $this->whatsapp->sendMessage($sender, "Commands available: status, help"),
            default => null
        };
    }
}
```

---

## License

This bundle is open-source software licensed under the **MIT License**.
