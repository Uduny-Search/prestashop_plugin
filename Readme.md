# Uduny – Search Integrator (PrestaShop)

PrestaShop module that exports your catalog to **Uduny Search** as a JSON/XML feed and loads the Uduny search script on your storefront.

- Product feed with variants, images, categories, stock, EAN/reference
- **Multicurrency & multicountry prices** (`prices` array), in the same format as the Uduny Magento plugin 1.2.0
- Storefront integration: search script, visitor session, cart and order tracking

Current version: **1.0.8**

## Requirements

- PrestaShop 1.7, 8.x or 9.x (tested on 9.2.1)
- PHP 7.2+

## Installation

**From zip:** Back office → Modules → Module Manager → *Upload a module* → select the zip.

**From git:**
```bash
cd /path/to/prestashop/modules
git clone https://github.com/Uduny-Search/prestashop_plugin.git udsearchintegrator
php ../bin/console prestashop:module install udsearchintegrator
php ../bin/console cache:clear
```

The folder **must** be named `udsearchintegrator`.

Updating from an older version runs the scripts in `upgrade/` automatically (Module Manager → *Upgrade*).

## Configuration

Back office → Modules → **Uduny - Search Integrator** → *Config*.

| Setting | Description |
|---|---|
| Script URL | Uduny search script URL. The storefront integration is only loaded when this is set. |
| Production mode | When off, the script only loads with `?udsi_test=testing` in the URL (preview mode). |
| Image size | Image type used for product images in the feed. |
| Server whitelist | Comma-separated IPs allowed to read the feed. Empty = public. |
| Master token | Lets non-whitelisted clients read the feed with `&token=...`. |
| Pricelist mode | `auto` (default): add `prices` only when the shop has more than one currency, location or shop. `always`: always add it. `never`: never add it. |
| Currency location map | One per line, `CURRENCY=COUNTRY_ISO`, e.g. `USD=US`, `GBP=GB`. Sets which country (and therefore tax) is used for a **non-default** currency. |
| Search URL | Uduny search page URL, passed to the script. |
| Trigger clicked / typing | CSS selectors of the elements that open the search on click / while typing. |

## Feeds

Create a feed in **Dashboard**: give it a slug and choose the filters (active status, stock, included/excluded categories, brands).

```
GET https://your-shop.com/search-export/{slug}
```

Without friendly URLs:
```
GET https://your-shop.com/index.php?fc=module&module=udsearchintegrator&controller=feed&link_rewrite={slug}
```

| Parameter | Default | Description |
|---|---|---|
| `limit` | 100 | Products per page |
| `offset` | 0 | Pagination offset |
| `request_total=1` | – | Add the total product count |
| `modified_from=YYYY-MM-DD` | – | Only products updated after this date |
| `xml=1` | – | XML output instead of JSON |
| `token` | – | Master token (needed when the IP whitelist is on) |

### Feed format

```json
{
  "total": 19,
  "items": [
    {
      "id": 1,
      "name": "Hummingbird printed t-shirt",
      "url": "https://your-shop.com/1-hummingbird-printed-t-shirt.html",
      "reference": "demo_1",
      "price": 23.9,
      "sale_price": 19.12,
      "categories": ["Home", "Clothes", "Men"],
      "prices": [
        { "location": "IT", "currency": "EUR", "price": 29.16, "sale": 23.33 },
        { "location": "US", "currency": "USD", "price": 26.2,  "sale": 20.96 }
      ],
      "variants": [
        { "variant_id": 1, "prices": [ "..." ] }
      ]
    }
  ]
}
```

`price` / `sale_price` are unchanged from earlier versions (shop default currency and country).

### How `prices` is built

1. **One entry per location/currency pair.**
   - Each shop adds its default country with its default currency.
   - Each other enabled currency gets its country from the *Currency location map*. If it isn't mapped, a built-in table is used (USD→US, GBP→GB, …). If it isn't there either, the shop's country is used.
   - Duplicate pairs are removed.
2. **Prices use PrestaShop's own price calculation:**
   - converted with the currency exchange rate
   - **tax included**, using that entry's country
   - specific prices (per currency, per country, by date) and catalog price rules apply
3. **`sale`** is the discounted price, or `0` when there is no discount.
4. **Parent products** show the cheapest variant for each pair. Products without combinations use their own price.
5. **Multishop:** a product only gets entries for the shops it is assigned to.

Tip: when the default currency is EUR, set the shop's default country to a real EU country (e.g. Italy) so the EUR entry gets the right VAT. The map does not change the default currency.

## Storefront

When *Script URL* is set (and production mode is on, or `?udsi_test=testing` is in the URL), the module adds:

- the Uduny search script
- `window.UdunySearch` – search config (`url`, `lang`, `trigger_elements`) and the visitor's `user_session`
- `window.UdunyStoreContext` – the visitor's `{ "country": "US", "currency": "USD" }` (when prices are active), so search results show the right price

It also sends cart updates and confirmed orders to Uduny.

## Local testing

```bash
# first product, with prices
curl -s "http://localhost:8080/search-export/test?limit=1" | jq '.items[0] | {id, price, sale_price, prices}'

# variant prices
curl -s "http://localhost:8080/search-export/test?limit=1" | jq '[.items[0].variants[] | .prices]'

# XML
curl -s "http://localhost:8080/search-export/test?limit=1&xml=1"
```

## Changelog

### 1.0.8
- Added `prices` array (multicurrency / multicountry / multishop) to products and variants
- New settings: Pricelist mode, Currency location map, Search URL, trigger selectors
- Storefront `UdunyStoreContext` and `UdunySearch` config
- Excluded-category filter; REF/EAN added to the description
- Fixed XML export
- Fixed SQL injection in the feed `link_rewrite` parameter
- JSON prices no longer have long decimals (`23.9` instead of `23.899999999999999`)

## License

Commercial license – © PT. Jawa Kreatif Design / Unusual Dope (info@unusualdope.com). Do not use, copy, change or share without written permission.
