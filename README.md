# Magento2-CatalogSync
Enables full catalog sync between [Sqquid](https://sqquid.com) and Magento 2. 

### What is Sqquid?
Sqquid is a catalog management system that supports multi channels and a long list of POS and ERP systems. We currently support RunIt POS, Retail Pro, Magento 1, and Magento 2.

Learn more: [Sqquid.com](https://sqquid.com)


### Installation

#### Composer
It's a good way to stay up to date with the latest version.

1. Run `composer require sqquid/module-sync`
2. Update Magento2 `php bin/magento update`
3. Clear the cache `php bin/magento cache:flush`
4. Compile `php bin/magento setup:di:compile`

#### Installation Video

Here's a short video on how to install our Sqquid Sync extension in under 5 minutes: [Sqquid Sync Installation on Magento 2 Video](https://www.youtube.com/watch?v=NjluSXcYMhk&t=3s)

