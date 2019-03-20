# Spark Real Estate Listings for WordPress

Wordpress Plugin for real estate search and listings, pulled from Spark API

- Connects to the Spark real estate API (API Key required)
- Lists results from API and creates posts of custom-post-type 'listing' for each one
- Downloads photos from the API locally (good for SEO)
- Loads photos from the API's CDN at first, then saves them locally, invisibly, in the background if they don't already exist on the local server (huge performance boost because you're not waiting on a ton of hi-res images)
- Custom jQuery slideshow plugin for displaying photos
- Allows for search, filtering based on price, location, type, style, etc
- Loads more results asynchronously
- Custom rewrite rules through WordPress
- Integrates Google Maps