# Apache rewrite / route inventory

Extracted from the `.htaccess` files in the three accessible source trees. Rules are shown verbatim with line numbers.

## `os` — `/tmp/audit/os/public_html/.htaccess`

- Lines: 74
- RewriteRule lines: 44

| Line | Rule |
|---:|---|
| 5 | `RewriteRule ^_database(?:/|$) - [F,L]` |
| 6 | `RewriteRule ^_private(?:/|$) - [F,L]` |
| 7 | `RewriteRule ^_templates(?:/|$) - [F,L]` |
| 8 | `RewriteRule ^(?:wx-install|wx-check|wx-demo)\.php$ - [F,L]` |
| 9 | `RewriteRule ^(?:error_log|php_errorlog)$ - [F,L]` |
| 31 | `RewriteRule ^ https://%1%{REQUEST_URI} [R=301,L]` |
| 34 | `RewriteRule ^ - [G,L]` |
| 36 | `RewriteRule ^$ /? [R=301,L]` |
| 37 | `RewriteRule ^contact\-us\-2/?$ /contact/ [R=301,L]` |
| 38 | `RewriteRule ^design\-planning/?$ /interior-design/ [R=301,L]` |
| 39 | `RewriteRule ^page(/.*)?$ / [R=301,L]` |
| 40 | `RewriteRule ^services/?$ /interior-design/ [R=301,L]` |
| 41 | `RewriteRule ^service/?$ /interior-design/ [R=301,L]` |
| 42 | `RewriteRule ^studio/?$ /about/ [R=301,L]` |
| 43 | `RewriteRule ^portfolio/?$ /projects/ [R=301,L]` |
| 44 | `RewriteRule ^journal/?$ /insights/ [R=301,L]` |
| 45 | `RewriteRule ^free\-consultation/?$ /contact/ [R=301,L]` |
| 46 | `RewriteRule ^interior\-design/bedroom/?$ /residential-bedroom-design/ [R=301,L]` |
| 47 | `RewriteRule ^interior\-design/home\-office/?$ /residential-home-office-design/ [R=301,L]` |
| 48 | `RewriteRule ^interior\-design/kitchen/?$ /kitchen-design/ [R=301,L]` |
| 49 | `RewriteRule ^architecture/educational/?$ /educational-buildings-design/ [R=301,L]` |
| 50 | `RewriteRule ^architecture/master\-planning/?$ /master-planning/ [R=301,L]` |
| 51 | `RewriteRule ^renovation/residential/?$ /residential-renovation/ [R=301,L]` |
| 52 | `RewriteRule ^renovation/office/?$ /office-renovation/ [R=301,L]` |
| 53 | `RewriteRule ^renovation/commercial/?$ /commercial-renovation/ [R=301,L]` |
| 54 | `RewriteRule ^renovation/retail/?$ /retail-showroom-renovation/ [R=301,L]` |
| 55 | `RewriteRule ^commercial/restaurant/?$ /restaurant-interior-design/ [R=301,L]` |
| 56 | `RewriteRule ^commercial/beauty\-salon/?$ /beauty-salon-design/ [R=301,L]` |
| 57 | `RewriteRule ^3d\-studio/?$ /3d-visualization/ [R=301,L]` |
| 58 | `RewriteRule ^project\-the\-open\-house/?$ /projects/ [R=301,L]` |
| 59 | `RewriteRule ^insight\-layering\-light/?$ /insights/ [R=301,L]` |
| 60 | `RewriteRule ^insights/small\-rooms/?$ /insights/small-space-ideas/ [R=301,L]` |
| 61 | `RewriteRule ^insights/costs\-in\-pakistan/?$ /insights/interior-design-cost-pakistan/ [R=301,L]` |
| 62 | `RewriteRule ^gratis\-gokkasten\-spelen\-netent/?$ - [G,L]` |
| 63 | `RewriteRule ^777\-nl\-casino\-nl\-2025\-review/?$ - [G,L]` |
| 64 | `RewriteRule ^He&/?$ - [G,L]` |
| 65 | `RewriteRule ^cgi\-sys/suspendedpage\.cgi/?$ - [G,L]` |
| 66 | `RewriteRule ^Figuren\-itm\-60842(/.*)?$ - [G,L]` |
| 67 | `RewriteRule ^wp\-admin(/.*)?$ - [G,L]` |
| 68 | `RewriteRule ^wp\-content(/.*)?$ - [G,L]` |
| 69 | `RewriteRule ^wp\-includes(/.*)?$ - [G,L]` |
| 70 | `RewriteRule ^feed(/.*)?$ - [G,L]` |
| 71 | `RewriteRule ^wp\-login\.php/?$ - [G,L]` |
| 72 | `RewriteRule ^xmlrpc\.php/?$ - [G,L]` |

## `site` — `/tmp/audit/site/.htaccess`

- Lines: 137
- RewriteRule lines: 24

| Line | Rule |
|---:|---|
| 16 | `RewriteRule ^ https://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]` |
| 20 | `RewriteRule ^ https://www.woodexurniture.pk%{REQUEST_URI} [L,R=301]` |
| 44 | `RewriteRule ^ %1 [L,R=301]` |
| 49 | `RewriteRule ^shop$ shop.php [L]` |
| 50 | `RewriteRule ^material-and-color$ material.php [L]` |
| 51 | `RewriteRule ^terms-and-conditions$ terms.php [L]` |
| 52 | `RewriteRule ^blog$ all-blogs.php [L]` |
| 53 | `RewriteRule ^categories$ allservices.php [L]` |
| 54 | `RewriteRule ^services$ servicelist.php [L]` |
| 55 | `RewriteRule ^contact-us$ contact.php [L]` |
| 56 | `RewriteRule ^checkout$ checkout.php [L]` |
| 57 | `RewriteRule ^about-us$ about.php [L]` |
| 58 | `RewriteRule ^privacy-policy$ privacypolicy.php [L]` |
| 59 | `RewriteRule ^faqs$ faqs.php [L]` |
| 60 | `RewriteRule ^shipping-and-returns$ shipping.php [L]` |
| 65 | `RewriteRule ^cart$ shoppingcart.php [L]` |
| 67 | `RewriteRule ^categories/([a-zA-Z0-9-]+)$ servicedetail.php?cat_url=$1 [L,QSA]` |
| 68 | `RewriteRule ^blog/([a-zA-Z0-9-]+)$ blog-single.php?slug=$1 [L,QSA]` |
| 69 | `RewriteRule ^shop/([a-zA-Z0-9-]+)$ singleproduct.php?proslug=$1 [L,QSA]` |
| 70 | `RewriteRule ^thank-you/([a-zA-Z0-9-]+)$ order_success.php?order_number=$1 [L,QSA]` |
| 71 | `RewriteRule ^review/([a-zA-Z0-9-]+)$ review.php?order=$1 [L,QSA]` |
| 72 | `RewriteRule ^thanks-for-the-review$ review_thanks.php [L]` |
| 75 | `RewriteRule ^search$ search_results.php [L,QSA]` |
| 133 | `RewriteRule .* - [F]` |

## `frontstore` — `/tmp/audit/rarout/frontstore/.htaccess`

- Lines: 224
- RewriteRule lines: 73

| Line | Rule |
|---:|---|
| 17 | `RewriteRule ^ https://woodexfurniture.pk%{REQUEST_URI} [L,R=301]` |
| 22 | `RewriteRule ^ https://woodexfurniture.pk%{REQUEST_URI} [L,R=301]` |
| 36 | `RewriteRule ^ %1 [L,R=301]` |
| 42 | `RewriteRule ^index\.php$ / [L,R=301]` |
| 45 | `RewriteRule ^shop\.php$ shop [L,R=301]` |
| 47 | `RewriteRule ^sale\.php$ sale [L,R=301]` |
| 49 | `RewriteRule ^projects\.php$ projects [L,R=301]` |
| 51 | `RewriteRule ^equotation\.php$ e-quotation [L,R=301]` |
| 53 | `RewriteRule ^quotation-basket\.php$ requested-quote [L,R=301]` |
| 55 | `RewriteRule ^quotation-success\.php$ quotation-success [L,R=301]` |
| 57 | `RewriteRule ^material\.php$ material-and-color [L,R=301]` |
| 59 | `RewriteRule ^terms\.php$ terms-and-conditions [L,R=301]` |
| 61 | `RewriteRule ^all-blogs\.php$ blog [L,R=301]` |
| 63 | `RewriteRule ^servicelist\.php$ services [L,R=301]` |
| 65 | `RewriteRule ^contact\.php$ contact-us [L,R=301]` |
| 67 | `RewriteRule ^checkout\.php$ checkout [L,R=301]` |
| 69 | `RewriteRule ^about\.php$ about-us [L,R=301]` |
| 71 | `RewriteRule ^privacypolicy\.php$ privacy-policy [L,R=301]` |
| 73 | `RewriteRule ^faqs\.php$ faqs [L,R=301]` |
| 75 | `RewriteRule ^shipping\.php$ shipping-and-returns [L,R=301]` |
| 77 | `RewriteRule ^warranty\.php$ warranty [L,R=301]` |
| 79 | `RewriteRule ^shopppingcart\.php$ cart [L,R=301]` |
| 81 | `RewriteRule ^showrooms\.php$ showrooms [L,R=301]` |
| 83 | `RewriteRule ^b2b\.php$ b2b [L,R=301]` |
| 85 | `RewriteRule ^factorydirect\.php$ factory-direct [L,R=301]` |
| 87 | `RewriteRule ^deliveryandinstallation\.php$ delivery-installation [L,R=301]` |
| 89 | `RewriteRule ^projectbased\.php$ project-based [L,R=301]` |
| 91 | `RewriteRule ^space_planing\.php$ space-planning [L,R=301]` |
| 95 | `RewriteRule ^categories/([^/]+)\.php$ categories/$1 [L,R=301]` |
| 97 | `RewriteRule ^blog-single\.php$ blog/%1? [L,R=301]` |
| 99 | `RewriteRule ^project-detail\.php$ projects/%1? [L,R=301]` |
| 101 | `RewriteRule ^singleproduct\.php$ shop/%1? [L,R=301]` |
| 103 | `RewriteRule ^servicedetail\.php$ categories/%1? [L,R=301]` |
| 105 | `RewriteRule ^order_success\.php$ thank-you/%1? [L,R=301]` |
| 107 | `RewriteRule ^review\.php$ review/%1? [L,R=301]` |
| 109 | `RewriteRule ^review_thanks\.php$ thanks-for-the-review [L,R=301]` |
| 111 | `RewriteRule ^search_results\.php$ search [L,R=301]` |
| 116 | `RewriteRule ^shop$ shop.php [L]` |
| 117 | `RewriteRule ^sale$ sale.php [L]` |
| 118 | `RewriteRule ^projects$ projects.php [L]` |
| 119 | `RewriteRule ^e-quotation$ equotation.php [L]` |
| 120 | `RewriteRule ^requested-quote$ quotation-basket.php [L]` |
| 121 | `RewriteRule ^quotation-success$ quotation-success.php [L]` |
| 122 | `RewriteRule ^material-and-color$ material.php [L]` |
| 123 | `RewriteRule ^terms-and-conditions$ terms.php [L]` |
| 124 | `RewriteRule ^blog$ all-blogs.php [L]` |
| 125 | `RewriteRule ^categories$ servicelist.php [L]` |
| 126 | `RewriteRule ^services$ servicelist.php [L]` |
| 127 | `RewriteRule ^contact-us$ contact.php [L]` |
| 128 | `RewriteRule ^contact$ contact.php [L]` |
| 129 | `RewriteRule ^checkout$ checkout.php [L]` |
| 130 | `RewriteRule ^about-us$ about.php [L]` |
| 131 | `RewriteRule ^privacy-policy$ privacypolicy.php [L]` |
| 132 | `RewriteRule ^faqs$ faqs.php [L]` |
| 133 | `RewriteRule ^shipping-and-returns$ shipping.php [L]` |
| 134 | `RewriteRule ^warranty$ warranty.php [L]` |
| 135 | `RewriteRule ^showrooms$ showrooms.php [L]` |
| 136 | `RewriteRule ^b2b$ b2b.php [L]` |
| 137 | `RewriteRule ^factory-direct$ factorydirect.php [L]` |
| 138 | `RewriteRule ^delivery-installation$ deliveryandinstallation.php [L]` |
| 139 | `RewriteRule ^project-based$ projectbased.php [L]` |
| 140 | `RewriteRule ^space-planning$ space_planing.php [L]` |
| 142 | `RewriteRule ^cart$ shopppingcart.php [L]` |
| 144 | `RewriteRule ^categories/([a-zA-Z0-9-]+)$ servicedetail.php?cat_url=$1 [L,QSA]` |
| 145 | `RewriteRule ^blog/([a-zA-Z0-9-]+)$ blog-single.php?slug=$1 [L,QSA]` |
| 146 | `RewriteRule ^projects/([a-zA-Z0-9-]+)$ project-detail.php?slug=$1 [L,QSA]` |
| 147 | `RewriteRule ^shop/([a-zA-Z0-9-]+)$ singleproduct.php?proslug=$1 [L,QSA]` |
| 148 | `RewriteRule ^thank-you/([a-zA-Z0-9-]+)$ order_success.php?order_number=$1 [L,QSA]` |
| 149 | `RewriteRule ^review/([a-zA-Z0-9-]+)$ review.php?order=$1 [L,QSA]` |
| 150 | `RewriteRule ^thanks-for-the-review$ review_thanks.php [L]` |
| 153 | `RewriteRule ^search$ search_results.php [L,QSA]` |
| 162 | `RewriteRule ^ redirect.php [L,QSA]` |
| 220 | `RewriteRule .* - [F]` |

