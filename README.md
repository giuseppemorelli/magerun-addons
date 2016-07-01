GMdotnet_MagerunAddons
=======================

Additional commands for [n98-magerun](https://github.com/netz98/n98-magerun).

#### This is a tools only for development environment. Please, do not use in production environment! 

## Installation

Official guide: [n98-magerun docs](http://magerun.net/introducting-the-new-n98-magerun-module-system/)

#### A global folder for the system

Clone the repository inside the folder:
```
/usr/local/share/n98-magerun/modules/
```

#### A folder inside the user’s home dir
Clone the repository inside the folder:
```
~/.n98-magerun/modules/
```

#### A folder inside the Magento installation (manually)
Copy the repository (not clone in case you have already git on your project) inside the folder:
```
MAGENTO_ROOT/lib/n98-magerun/modules/
```
 
#### A folder inside the Magento installation (with composer)

Add the repository GMdotnet_MagerunAddons to your composer:

```
{
  ...
  "repositories": [
    ...
    {
      "type": "vcs",
      "url": "https://github.com/gmdotnet/GMdotnet_MagerunAddons.git"
    }
    ...
  ]
  ...
}
```

Install the module

```
composer require gmdotnet/magerun-addons 1.0.x-dev
```

(you need [modman](https://github.com/colinmollenhour/modman) as a pre-requisite)



## Commands

### Create Dummy Products ###

(experimental). Create dummy products with all default vanilla magento or your custom values.

**Interactive mode** or via **shell arguments** or mixed.

```
$ n98-magerun.phar product:create:dummy
```

Argument             | Description                                                         | Accepted Values                                                                                                                               |
:------------------- | :------------------------------------------------------------------ | :-------------------------------------------------------------------------------------------------------------------------------------------- |
`attribute-set-id`   | Attribute Set Id (default: Default with ID 4)                       | only integer
`product-type`       | Product Type (default: simple)                                      | `simple`<br />`configurable`<br />[grouped - work in progress]
`sku-prefix`         | Prefix for product's sku (default: MAGPROD-)                        | any
`category-ids`       | Categories for product association (comma separated - default null) | only integer with comma separated
`product-status`     | Product Status (default: enabled)                                   | only integer <br /> `1` - for enabled <br /> `2` - for disabled
`product-visibility` | Product Visibility (default: visibile_both)                         | only integer <br /> `1` - for not visible <br /> `2` - for visible in catalog <br /> `3` - for visible in search <br /> `4` - for visible in both
`product-number`     | Number of products to create                                        | only integer


Extra options asked only for configurabile products (actually **only in interactive mode**)

Argument                        | Description                                                    | Accepted Values     |
:------------------------------ | :------------------------------------------------------------- | :------------------ |
`attribute-configurable-number` | Number of configurable attributes to use ("super attributes")  | only integer
`attribute-configurable-codes`  | Attribute codes for configurable products ("super attributes") | only text
`product-children-number`       | Number of products children                                    | only integer 


####INFO

#####IMAGES
- Product's temp images are saved into `MAGENTO_ROOT/media/import/` folder with this filename: <br />`<SKU-PREFIX><COUNTER>."-".sha1(<SKU-PREFIX><COUNTER>).".jpg"`<br /><br />After creation you can clean the folder if you want.

#####CONFIGURABLE PRODUCTS
- You need to create configurable attributes and insert same values. Then add these attributes to attribute set you want use

### Create Dummy Categories ###

(experimental). Create dummy categories with all default vanilla magento or your custom values.

**Interactive mode** or via **shell arguments** or mixed.

```
$ n98-magerun.phar category:create:dummy
```

Argument                     | Description                                                                                 | Accepted Values                                  |
:--------------------------- | :------------------------------------------------------------------------------------------ | :----------------------------------------------- |
`children-categories-number` | Number of children for each category created (default: 0 - use '-1' for random from 0 to 5) | only integer or -1 for random number from 0 to 5 |
`category-name-prefix`       | Category Name Prefix (default: 'My Awesome Category')                                       | any                                              |
`category-number`            | Number of categories to create (default: 1)                                                 | only integer                                     |

## Requirements
- (tested with n98-magerun 1.96.1)
- (tested with magento 1.9.x)
- (tested with php 5.6)

## Version
- 0.0.1 (beta)

## WORK IN PROGRESS
- create dummy grouped products
- create dummy virtual products
- create dummy bundle products

## Contribution
Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).<br />Please create your pull request against the `develop` branch

## License
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

## Credits

Thanks to [NETZ98](http://www.netz98.de/) for creating this incredible tools.<br />
Thanks to [Kalen Jordan](https://github.com/kalenjordan) and [Peter Jaap Blaakmeer](https://github.com/peterjaap) to start contributing n98-magerun with their addons.<br />
Thanks to [lorempixel.com](http://lorempixel.com) for the sample images.