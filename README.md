GMdotnet_MagerunAddons
=======================

Additional commands for [n98-magerun](https://github.com/netz98/n98-magerun).

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

(experimental). Create dummy products (simple) with all default vanilla magento or your custom value.

**Interactive mode** or via **shell arguments** or mixed.

```
$ n98-magerun.phar product:create:dummy
```

Argument             | Description                                                         | Accepted Values                                                                                                                               |
:------------------- | :------------------------------------------------------------------ | :-------------------------------------------------------------------------------------------------------------------------------------------- |
`attribute-set-id`   | Attribute Set Id (default: Default with ID 4)                       | only integer
`product-type`       | Product Type (default: simple)                                      | `simple` [configurable - work in progress ] [grouped - work in progress]
`sku-prefix`         | Prefix for product's sku (default: MAGPROD-)                        | any
`category-ids`       | Categories for product association (comma separated - default null) | only integer with comma separated
`product-status`     | Product Status (default: enabled)                                   | only integer <br /> `1` - for enabled <br /> `2` - for disabled
`product-visibility` | Product Visibility (default: visibile_both)                         | only integer <br /> `1` - for not visible <br /> `2` - for visible in catalog <br /> `3` - for visible in search <br /> `4` - for visible in both
`product-number`     | Number of products to create                                        | only integer

## WORK IN PROGRESS
- create dummy configurable products
- create dummy grouped products

## Contribution
Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).<br />Please create your pull request against the `develop` branch

## License
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

## Credits

Thanks to [NETZ98](http://www.netz98.de/) for creating this incredible tools.<br />
Thanks to [Kalen Jordan](https://github.com/kalenjordan) and [Peter Jaap Blaakmeer](https://github.com/peterjaap) to start contributing n98-magerun with their addons.<br />
Thanks to [lorempixel.com](http://lorempixel.com) for the sample images.