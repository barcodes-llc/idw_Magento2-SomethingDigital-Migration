# SomethingDigital_Migration

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

## Create migration for CMS blocks/pages with Bluefoot content

```bash
# Create a migration with code which will create CMS block based on data of existing CMS block (specified by identifier).
php bin/magento migrate:make --create-from-block=block_identifier --module=SomethingDigital_MyModule CreateFancyPage

# Create a migration with code which will update CMS block based on data of existing CMS block (specified by identifier).
# You have to make necessary changes into CMS block data in a code inside migration script.
php bin/magento migrate:make --update-from-block=block_identifier --module=SomethingDigital_MyModule CreateFancyPage

# Create a migration with code which will create CMS page based on data of existing CMS page (specified by identifier).
php bin/magento migrate:make --create-from-page=page_identifier --module=SomethingDigital_MyModule CreateFancyPage

# Create a migration with code which will update CMS page based on data of existing CMS page (specified by identifier).
# You have to make necessary changes into CMS page data in a code inside migration script.
php bin/magento migrate:make --update-from-page=page_identifier --module=SomethingDigital_MyModule CreateFancyPage
```

# Helpers
The module predefines four helpers:

**$page** - For CMS pages

**$block** - For CMS blocks

**$email** - For Email Templates

**$resourceConfig** - For core_config_data

### PageHelper $page

#### Usage
```php
# Call the create function
$this->page->create(
  'identifier', //page identifier, i.e. my-page
  'Title', //Title of the page i.e. My Page
  'content', //Static Page Content goes here i.e. html
  mixed[] // extra parameters you want to set
);
```
- Extra parameters:
  - is_active
  - store_id
  - custom_root_template

### BlockHelper $block

#### Usage
```php
# Call the create function
$this->block->create(
  'identifier', //page identifier, i.e. my-block
  'Title', //Title of the page i.e. My Block
  'content', //Static Block Content goes here i.e. html
  mixed[] // extra parameters you want to set
);
```
- Extra parameters:
  - is_active
  - store_id

### EmailHelper $email

#### Usage
```php
# Call the create function
$this->email->create(
  'identifier', //page identifier, i.e. my-email
  'Subject', //Title of the page i.e. My Email
  'content', //Content i.e. email body
  mixed[] // extra parameters you want to set
);
```
* Extra fields:
  - template_subject
  - template_styles
  - template_type
  - template_sender_name
  - template_sender_email
  - orig_template_code
  - orig_template_variables

* **Subject/Body/extra parameters example:**
```php
# Extra parameters are generally optional.
$subject = '{{trans "Your %store_name order confirmation" store_name=$store.getFrontendName()}}';
$body = '<p>We hope to see you again.</p>';
$mixed = [
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
}',]

$this->email->create(
  'identifier', //page identifier, i.e. my-email
  $subject, //Title of the page i.e. My Email
  $body, //Content i.e. email body
  $mixed // extra parameters you want to set
);
```

### ResourceConfig $resourceConfig

#### Usage
```php
# Call the create function
$this->resourceConfig->saveConfig(
  'path/to/thing', //path, i.e. google/analytics/active
  value, //Value column, i.e. 1
  'scope', //What scope to be used in, i.e. 'default'
  scopeId //i.e. 0
);
```

### <Model> Attributes

You can also add customerAttributes, (or other Attributes as well) in here, you just have to add the proper factory.

**Example**

For customer attribute specifically (this will be different for other Attributes), add CustomerSetupFactory into the `php __construct(Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory)` function.
Inside of execute, configure your attribute after `startSetup()` as follows by changing the required values, or adding any that you need:
```php
$customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
$customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'attribute_name', [
    'type' => 'datetime', //the type of attribute.
    'label' => 'Attribute Name',
    'input' => 'date',
    'required' => false,
    'system' => false,
    'user_defined' => false,
    'group' => 'General',
    'unique' => false,
    'sort_order' => 310,
    'position' => 310
]);
```


## Compatibility

 * Magento 2.1.x
