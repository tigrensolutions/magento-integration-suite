## Overview
Magento 2 API for Mobile App was built to decrease burden of the developers when developing a new mobile app, regardless of native apps or hybrid apps. Besides the main purpose of connecting Magento 2 websites to the apps, we can use this API to govern how Magento can interact with other third-party software.

## What is this for? 
Magento 2 API by Tigren helps to integrate third-party applications/ external systems (e.g.: mobile apps, CRM, ERP, CMS...) with Magento 2 websites in a consistent and robust way. 
Magento 2 API by Tigren is using GraphQL, REST (Representational State Transfer), and SOAP (Simple Object Access Protocol).

## Outstanding features
Besides delivering the same functions as API provided by Magento, Magento 2 API by Tigren added more essential features:
* Catalog GraphQL
  * Get more data for product detail: is_available, attributes, related products...
  * Get more data for category: layer navigation, category tree (display mode, image thumbnail)
  * Get new products, featured products
* Wishlist GraphQL
  * Add product to wishlist
  * Remove from wishlist
  * Add to cart from wishlist 
  * Add all items in wishlist to cart
  * Get wishlist data
* Comparison GraphQL
  * Add product to compare
  * Remove from comparison list
  * Get comparison data
* Customer
  * Assign order
  * Assign guest cart
  * Reorder
  * Create new password
  * Get all orders 
  * Submit contact form
* Store GraphQL
  * Get more config data
  
See the full feature list of Magento 2 API by Tigren [here](https://www.tigren.com/magento-2-extensions/magento-2-api-for-mobile-app/)

## Installation

In your Magento 2 root directory, you may install this package via composer:

`composer require tigrensolutions/magento-integration-suite`

`php bin/magento setup:upgrade`


## Help
https://github.com/tigrensolutions/magento-integration-suite/issues 


