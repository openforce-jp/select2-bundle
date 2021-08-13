select2-bundle
====================

## Introduction

This is a symfony Entity Form Type that supports select2 without using a controller.
It's so easy and simple.

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.


### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require openforce/select2-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    \Openforce\Select2Bundle\OpenforceSelect2Bundle::class => ['all' => true],
];
```

### Step 3: Configure twig.yaml file

```yaml
twig:
    
    #append this parameters
    paths:
        '%kernel.project_dir%/vendor/openforce/select2-bundle/src/Resources/views': OpenforceSelect2    
    form_themes:
        - "@OpenforceSelect2/select2_type.html.twig"
```

### Step 4: Add select2 to your template

```html
//Add your template file
<script src="//code.jquery.com/jquery-1.9.0rc1.js"></script>
<link href="//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

```

How to use
----------------------------------

The main options are:

- `class` Required. Your target entity.
- `search_field` Required. Entity field name you want to use for your search. 
- `choice_label` Optional. Entity field name you want to display on the label. default value is `__toString`.
- `max_results` Optional. Number to display on page.
- `filter` Optional. It is callback function. If you use this, you don't need to define `search_field`.

```php
// your Controller file
    public function index(){

        $formBuildr = $this->createFormBuilder();
        $formBuilder->add("product", Select2Type::class,[
            'class' => Product::class, 
            'search_field' => 'name', 
            'choice_label' => 'name',
            'max_results' => 50, 
            'filter' => function(EntityRepository $er, $value){
                return $er->createQueryBuilder("p")
                    ->where("p.description like :word")
                    ->setParameter("word", "%".$value."%")
                    ->orderBy("p.productId")
                    ;
            }
        ])
    }

```

