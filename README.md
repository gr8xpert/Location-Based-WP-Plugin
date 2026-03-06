# Lion Trust Locations

A WordPress plugin for managing location-based real estate pages with hierarchical parent-child structure, AJAX search, and SEO interlinking.

## Features

- **Hierarchical Location Pages** - Create parent locations with property type children (Apartments, Penthouses, Townhouses, Villas)
- **Bulk Creator** - Generate all 5 pages for a location in one click
- **AJAX Search** - Real-time search with grid results on the listing page
- **SEO Interlinking** - Automatic links between all pages in a location set
- **Nearby Locations** - Display related locations (manual selection or same-region fallback)
- **JSON-LD Schema** - Structured data for Google (Place, WebPage, BreadcrumbList)
- **Page Builder Compatible** - Works with Divi, LiveCanvas, Elementor, and others
- **REST API** - Full API for headless/custom implementations

## Installation

1. Download or clone this repository
2. Upload the `liontrust-locations` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin
4. Go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules

## Usage

### Creating Locations

**Option 1: Bulk Create (Recommended)**
1. Go to **Locations → Bulk Create**
2. Enter the location name (e.g., "Marbella")
3. Select a region
4. Choose which property types to create
5. Click **Create Location Set**

This creates:
- Marbella (parent page)
- Marbella Apartments (child)
- Marbella Penthouses (child)
- Marbella Townhouses (child)
- Marbella Villas (child)

**Option 2: Manual Creation**
1. Go to **Locations → Add New**
2. Create the parent location first
3. Create children and set the parent in "Page Attributes"

### URL Structure

```
/popular-locations/                     → Archive (listing page)
/popular-locations/marbella/            → Parent location page
/popular-locations/marbella/apartments/ → Child property type page
```

### Shortcodes

| Shortcode | Description | Parameters |
|-----------|-------------|------------|
| `[lt_search]` | AJAX search with grid results | `per_page`, `columns`, `show_region_filter` |
| `[lt_location_grid]` | Static grid of locations | `count`, `region`, `columns`, `ids` |
| `[lt_interlinking]` | Links to all 5 pages in set | `post_id`, `title` |
| `[lt_nearby_locations]` | Grid of nearby locations | `count`, `columns`, `title` |

**Examples:**
```
[lt_search per_page="12" columns="3"]
[lt_location_grid count="6" region="costa-del-sol"]
[lt_interlinking title="Explore This Area"]
[lt_nearby_locations count="12" columns="4"]
```

### REST API Endpoints

```
GET /wp-json/lt/v1/locations/search?s=marbella&page=1
GET /wp-json/lt/v1/locations/{id}/nearby
GET /wp-json/lt/v1/locations/{id}/children
```

## Taxonomies

- **Property Type** (`lt_property_type`) - Apartments, Penthouses, Townhouses, Villas
- **Region** (`lt_region`) - Costa del Sol, Costa Blanca, etc.

## Meta Fields

| Field | Description | Applies To |
|-------|-------------|------------|
| Short Description | Text shown on location cards | All locations |
| Nearby Locations | Manually selected nearby locations | Parent locations only |
| Latitude/Longitude | Coordinates for schema markup | Parent locations only |

## Theme Overrides

Templates can be overridden by copying them to your theme:

```
yourtheme/liontrust-locations/archive-lt_location.php
yourtheme/liontrust-locations/single-lt_location.php
```

## Hooks

**Actions:**
- `lt_before_location_content` - Before main content on single pages
- `lt_after_location_content` - After main content on single pages
- `lt_after_location_sections` - After interlinking/nearby sections

**Filters:**
- `lt_archive_title` - Modify archive page title
- `lt_archive_description` - Add archive page description
- `lt_show_featured_image` - Control featured image display
- `lt_load_public_assets` - Force load CSS/JS on specific pages

## File Structure

```
liontrust-locations/
├── liontrust-locations.php      # Main plugin file
├── uninstall.php                # Clean uninstall
├── includes/
│   ├── class-lt-loader.php      # Hook management
│   ├── class-lt-activator.php   # Activation tasks
│   ├── cpt/                     # CPT & Taxonomies
│   ├── meta/                    # Meta boxes
│   ├── api/                     # REST API
│   ├── query/                   # Nearby locations logic
│   ├── seo/                     # JSON-LD schema
│   └── helpers/                 # Helper functions
├── admin/                       # Admin functionality
├── public/                      # Frontend assets
├── templates/                   # Template files
├── shortcodes/                  # Shortcode handlers
└── languages/                   # Translation files
```

## Requirements

- WordPress 5.0+
- PHP 7.4+

## License

GPL-2.0+

## Author

Lion Trust
