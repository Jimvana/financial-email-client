# Financial Email Client

A WordPress plugin that connects to email accounts to analyze financial content in emails, such as bills, price increases, and subscription renewals.

## Features

- Connect to multiple email providers (Gmail, Outlook, Yahoo, and custom IMAP)
- Automatically scan emails for financial content
- Detect due dates for bills and payments
- Identify price increases from service providers
- Monitor subscription renewals
- Track payment confirmations
- View all financial insights in a dashboard
- Get alerts for upcoming payments and price changes

## Installation

1. Upload the `financial-email-client` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Financial Email' in the admin menu to configure the plugin

## Usage

Add the email client to any page using the shortcode:

```
[email_financial_client]
```

Add the financial insights dashboard using:

```
[financial_insights]
```

## Security

- Email credentials are encrypted in the database
- The plugin uses OAuth where available for secure authentication
- All sensitive data is handled securely

## Troubleshooting

- Diagnostic tool: `yoursite.com/wp-content/plugins/financial-email-client/direct-imap-test.php?key=your_secret_key_here`
- Debug mode: `yoursite.com/?fec_debug=1&key=your_secret_key`

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- PHP IMAP extension enabled
- PHP OpenSSL extension enabled

## Support

For support, please contact us at support@yourwebsite.com

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

[GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html)
