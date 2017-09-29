# SomethingDigital_Migration sans Bluefoot

This module allows other modules to use named migrations, rather than version numbers for data and other changes.

Mostly, this is useful for scripts which install CMS resources or similar.


## Workflow

```bash
# Create a migration: also creates Setup tooling if necessary.
php bin/magento migrate:make --module=SomethingDigital_MyModule CreateFancyPage

# Edit the migration, using helpers, as desired.
open app/code/SomethingDigital/MyModule/Migration/Data/*CreateFancyPage.php

# Run upgrades, migrations, etc.
php bin/magento setup:upgrade

# Re-execute latest migration after tweaking.
php bin/magento migrate:retry

# Commit when ready:
git add -p app/code/SomethingDigital/MyModule/{Setup,Migration}
```

## Helpers

CMS page, block, and email template helpers are available.

```php
    public function execute(SetupInterface $setup)
    {
        $this->page->create('migrate_page', 'Migration Page', 'Lots of content goes here.');
        $this->block->create('migrate_block', 'Migration Block', 'The static block content.');

        // Extra parameters are generally optional.
        $subject = '{{trans "Your %store_name order confirmation" store_name=$store.getFrontendName()}}';
        $body = '<p>We hope to see you again.</p>';
        $this->email->create('New Order', $subject, $body, [
            'template_styles' => '',
            'orig_template_code' => 'sales_email_order_template',
            'orig_template_variables' => '{
"var formattedBillingAddress|raw":"Billing Address",
"var order.getEmailCustomerNote()":"Email Order Note",
"var order.increment_id":"Order Id",
"layout handle=\"sales_email_order_items\" order=$order area=\"frontend\"":"Order Items Grid",
"var payment_html|raw":"Payment Details",
"var formattedShippingAddress|raw":"Shipping Address",
"var order.getShippingDescription()":"Shipping Description",
"var shipping_msg":"Shipping message"
}',
        ]);
    }
```


## Compatibility

 * Magento 2.1.x