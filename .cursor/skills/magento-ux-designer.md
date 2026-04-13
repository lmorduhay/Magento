# Magento UX Designer Skill

## When to Use
Use this skill when working on frontend themes, layouts, templates, CSS/LESS styling, JavaScript behavior, UI components, storefront user experience, admin panel UI, responsive design, or accessibility in Magento 2.

## Role Definition
You are a **Senior Magento 2 Frontend Developer & UX Specialist** who creates beautiful, performant, accessible, and conversion-optimized e-commerce experiences. You master both the Luma (default) and Hyvä theme stacks, and understand Magento's layout/template/block rendering pipeline.

---

## Theme Architecture

### Magento 2 has two major theme approaches:

| Aspect | Luma (Default) | Hyvä |
|--------|---------------|------|
| CSS | LESS preprocessor | Tailwind CSS |
| JS Framework | RequireJS + KnockoutJS + jQuery | Alpine.js (14KB) |
| Page Load | 3-6 seconds typical | 1-2 seconds typical |
| Static Files | ~200 files | 2-5 files |
| Learning Curve | High (complex stack) | Moderate (modern stack) |
| Template | PHTML + KnockoutJS templates | PHTML + Alpine.js |

### Theme Directory Structure (Luma-based)
```
app/design/frontend/Vendor/theme/
├── composer.json
├── registration.php
├── theme.xml                          # Theme declaration and parent
├── etc/
│   └── view.xml                       # Image sizes, gallery config
├── Magento_Theme/                     # Module-specific overrides
│   ├── layout/
│   │   └── default.xml                # Layout modifications
│   └── templates/
│       └── html/
│           ├── header.phtml
│           └── footer.phtml
├── Magento_Catalog/                   # Catalog module overrides
│   ├── layout/
│   │   ├── catalog_product_view.xml
│   │   └── catalog_category_view.xml
│   └── templates/
│       └── product/
│           └── view/
│               └── form.phtml
├── media/
│   └── preview.jpg                    # Theme preview image
└── web/
    ├── css/
    │   └── source/
    │       ├── _theme.less            # Theme variable overrides
    │       ├── _extend.less           # Additional styles
    │       └── _variables.less        # Variable definitions
    ├── fonts/
    ├── images/
    └── js/
```

### Theme Inheritance
- Themes inherit from a parent via `theme.xml`: `<parent>Magento/luma</parent>`.
- Only override files you want to change — everything else cascades from the parent.
- **Fallback order**: Child theme → Parent theme → Module view files.

---

## Layout System

### Layout XML Fundamentals
Magento uses XML-based layout to define page structure. Layout files are merged in this order:
1. Base layouts from modules (`view/frontend/layout/`)
2. Theme layouts (`Magento_Module/layout/`)
3. Database-stored layout updates

### Key Layout Concepts

**Handles** — Named layout configuration sets:
- `default` — Applied to all pages
- `catalog_product_view` — Product detail page
- `catalog_category_view` — Category listing page
- `checkout_index_index` — Checkout page
- `cms_index_index` — CMS homepage
- Custom handles: `<module>_<controller>_<action>`

**Containers** — Structural elements that hold blocks:
```xml
<container name="content" as="content" label="Main Content Area" htmlTag="main" htmlClass="page-main">
    <!-- blocks go here -->
</container>
```

**Blocks** — PHP classes that render HTML via templates:
```xml
<block class="Magento\Catalog\Block\Product\View"
       name="product.info"
       template="Magento_Catalog::product/view/form.phtml">
    <arguments>
        <argument name="viewModel" xsi:type="object">Vendor\Module\ViewModel\ProductInfo</argument>
    </arguments>
</block>
```

### Common Layout Operations

**Move a block:**
```xml
<move element="product.info.price" destination="product.info.main" after="product.info.title"/>
```

**Remove a block:**
```xml
<referenceBlock name="catalog.compare.sidebar" remove="true"/>
```

**Add CSS/JS:**
```xml
<head>
    <css src="css/custom.css"/>
    <script src="js/custom.js"/>
    <link src="https://fonts.googleapis.com/css?family=Roboto" src_type="url"/>
</head>
```

**Set page layout:**
```xml
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd"
      layout="2columns-left">
```

**Add body CSS class:**
```xml
<body>
    <attribute name="class" value="custom-page-class"/>
</body>
```

---

## Template Development (PHTML)

### Best Practices
- **Never put business logic in templates** — use ViewModels or Block methods.
- **Always escape output**:
  ```php
  <?= $block->escapeHtml($value) ?>
  <?= $block->escapeUrl($url) ?>
  <?= $block->escapeHtmlAttr($attr) ?>
  <?= /* @noEscape */ $block->getChildHtml('child.block') ?>
  ```
- Use `/* @noEscape */` only for HTML that is already escaped (like child block output).
- Access ViewModels in templates:
  ```php
  /** @var \Vendor\Module\ViewModel\ProductInfo $viewModel */
  $viewModel = $block->getData('viewModel');
  ```

### Template Override Priority
To override a core template, place it in your theme:
```
app/design/frontend/Vendor/theme/Magento_Catalog/templates/product/view/form.phtml
```
This overrides: `vendor/magento/module-catalog/view/frontend/templates/product/view/form.phtml`

---

## CSS/LESS Development (Luma Stack)

### Theme Variables (`_theme.less`)
Override Luma/Blank variables for global changes:
```less
@primary__color: #1979c3;
@secondary__color: #f7f7f7;
@link__color: #1979c3;
@link__hover__color: darken(@link__color, 15%);
@font-family__base: 'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
@font-size__base: 14px;
@button__color: #fff;
@button__background: @primary__color;
@layout__max-width: 1280px;
@navigation__background: #333;
```

### Extending Styles (`_extend.less`)
Use `_extend.less` for additional rules (merged after parent):
```less
.page-header {
    background-color: @primary__color;
    .header.content {
        padding-top: 15px;
        padding-bottom: 15px;
    }
}

.product-info-main {
    .page-title-wrapper {
        .page-title {
            font-size: 28px;
            font-weight: 700;
        }
    }
}
```

### Compile LESS in Development
```bash
# Using Grunt (traditional)
cp grunt-config.json.sample grunt-config.json
cp package.json.sample package.json
npm install
grunt exec:all        # Compile all themes
grunt less:theme      # Compile specific theme
grunt watch           # Watch for changes

# Or manually clear and regenerate
rm -rf pub/static/frontend/Vendor/theme/*
rm -rf var/view_preprocessed/*
php bin/magento cache:clean layout block_html full_page
# Styles regenerate on next page load in developer mode
```

---

## JavaScript Development (Luma Stack)

### RequireJS Configuration
Define JS modules in `requirejs-config.js`:
```javascript
var config = {
    map: {
        '*': {
            'customModule': 'Vendor_Module/js/custom-module'
        }
    },
    paths: {
        'slick': 'Vendor_Module/js/lib/slick.min'
    },
    shim: {
        'slick': {
            deps: ['jquery']
        }
    }
};
```

### JS Mixins (modify existing behavior)
```javascript
// requirejs-config.js
var config = {
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Vendor_Module/js/catalog-add-to-cart-mixin': true
            }
        }
    }
};

// js/catalog-add-to-cart-mixin.js
define(['jquery'], function ($) {
    'use strict';
    return function (widget) {
        $.widget('mage.catalogAddToCart', widget, {
            submitForm: function (form) {
                // Custom logic before add to cart
                this._super(form);
            }
        });
        return $.mage.catalogAddToCart;
    };
});
```

### KnockoutJS Templates
UI components use KnockoutJS for dynamic rendering:
```html
<!-- view/frontend/web/template/component.html -->
<div data-bind="scope: 'customComponent'">
    <!-- ko if: isVisible() -->
    <span data-bind="text: message"></span>
    <!-- /ko -->
</div>
```

---

## Hyvä Theme Development

### Child Theme Setup
```
app/design/frontend/Vendor/hyva-child/
├── registration.php
├── theme.xml              # parent: Hyva/default
├── web/
│   ├── tailwind/
│   │   ├── tailwind.config.js
│   │   ├── hyva.config.json
│   │   └── tailwind-source.css
│   └── css/
│       └── styles.css     # Compiled output
├── Magento_Theme/
│   └── templates/
│       └── html/
│           └── header.phtml
└── etc/
    └── view.xml
```

### Tailwind Development Workflow
```bash
cd app/design/frontend/Vendor/hyva-child/web/tailwind/
npm install
npm run watch   # Development (hot reload)
npm run build   # Production (minified)
```

### Alpine.js Components in Hyvä
```php
<!-- PHTML template with Alpine.js -->
<div x-data="initProductGallery()" x-init="init()">
    <template x-for="image in images" :key="image.id">
        <img :src="image.url" :alt="image.alt"
             @click="selectImage(image)"
             :class="{'ring-2 ring-blue-500': isActive(image)}"/>
    </template>
</div>

<script>
function initProductGallery() {
    return {
        images: <?= /* @noEscape */ $block->getGalleryImagesJson() ?>,
        activeImage: null,
        init() {
            this.activeImage = this.images[0];
        },
        selectImage(image) {
            this.activeImage = image;
        },
        isActive(image) {
            return this.activeImage?.id === image.id;
        }
    };
}
</script>
```

---

## UI Components (Admin)

### XML Definition (`view/adminhtml/ui_component/entity_listing.xml`)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">entity_listing.entity_listing_data_source</item>
        </item>
    </argument>
    <dataSource name="entity_listing_data_source" component="Magento_Ui/js/grid/provider">
        <settings>
            <requestFieldName>id</requestFieldName>
            <primaryFieldName>entity_id</primaryFieldName>
        </settings>
    </dataSource>
    <listingToolbar name="listing_top">
        <bookmark name="bookmarks"/>
        <columnsControls name="columns_controls"/>
        <filterSearch name="fulltext"/>
        <filters name="listing_filters"/>
        <paging name="listing_paging"/>
    </listingToolbar>
    <columns name="entity_columns">
        <selectionsColumn name="ids" sortOrder="0">
            <settings>
                <indexField>entity_id</indexField>
            </settings>
        </selectionsColumn>
        <column name="entity_id" sortOrder="10">
            <settings>
                <filter>textRange</filter>
                <label translate="true">ID</label>
                <sorting>asc</sorting>
            </settings>
        </column>
        <column name="name" sortOrder="20">
            <settings>
                <filter>text</filter>
                <label translate="true">Name</label>
            </settings>
        </column>
    </columns>
</listing>
```

---

## UX Best Practices for E-Commerce

### Performance (Core Web Vitals)
1. **LCP (Largest Contentful Paint)**: Optimize hero images, preload critical assets.
2. **FID/INP (Interaction to Next Paint)**: Minimize JS execution, defer non-critical scripts.
3. **CLS (Cumulative Layout Shift)**: Set explicit dimensions on images/embeds, reserve space for dynamic content.

### Conversion Optimization
- **Product pages**: Clear CTA button, visible price, trust signals, size/color selectors above fold.
- **Category pages**: Fast filtering, lazy-loaded images, clear product cards with price and reviews.
- **Checkout**: Minimize steps, show progress indicator, guest checkout option, auto-fill addresses.
- **Cart**: Easy quantity adjustment, clear item details, visible subtotal, cross-sell suggestions.
- **Mobile first**: Touch-friendly targets (min 44x44px), thumb-zone navigation, swipe gestures.

### Accessibility (WCAG 2.1 AA)
- Use semantic HTML (`<nav>`, `<main>`, `<article>`, `<aside>`).
- All images must have `alt` attributes (descriptive for content, empty for decorative).
- Ensure color contrast ratio ≥ 4.5:1 for normal text.
- Keyboard navigable: all interactive elements reachable via Tab, activated via Enter/Space.
- ARIA labels on custom interactive components.
- Form inputs must have associated `<label>` elements.
- Skip-to-content link at the top of the page.

---

## Responsive Breakpoints (Luma Default)

```less
@screen__xs: 480px;
@screen__s: 640px;
@screen__m: 768px;
@screen__l: 1024px;
@screen__xl: 1440px;
```

Magento uses a **mobile-first** approach in LESS:
```less
// Base styles (mobile)
.product-info { padding: 10px; }

// Tablet and up
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .product-info { padding: 20px; display: flex; }
}
```

---

## Common Frontend CLI Commands

```bash
# Clear static content for re-generation
rm -rf pub/static/frontend/* pub/static/adminhtml/*
rm -rf var/view_preprocessed/*

# Deploy static content for specific theme/locale
php bin/magento setup:static-content:deploy en_US -f --theme=Vendor/theme

# Clean relevant caches
php bin/magento cache:clean layout block_html full_page translate

# In developer mode, static files auto-generate on request.
# Just clear the caches and generated files, then reload the page.
```

---

## Frontend Debugging Tips

1. **Enable template path hints**: Stores → Configuration → Advanced → Developer → Debug → "Enabled Template Path Hints for Storefront" = Yes.
2. **Enable layout XML debug**: Add `?layoutDebug=1` to URL (requires developer mode).
3. **Browser DevTools**: Use Network tab to audit loaded CSS/JS count and sizes.
4. **Check generated CSS**: Inspect `pub/static/frontend/Vendor/theme/en_US/css/` for compiled output.
5. **JS debugging**: Open browser console, use `require('module')` to access RequireJS modules.
6. **KnockoutJS debugging**: Use browser's KO Context Debugger extension.
