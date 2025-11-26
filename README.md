# WP Travel Reservation Form

WordPress plugin for managing travel activities, clients, and itineraries with clear cost overviews.

## Features
- Custom post types for **Activities**, **Clients**, and **Itineraries**.
- Activity details include day assignment, description (via content editor), and cost tracking.
- Client records store contact information for managing bookings.
- Itinerary editor lets you pick a client, assign activities by day, and see automatic cost totals.
- `[travel_itinerary_overview id="123"]` shortcode displays an itinerary with per-activity and total costs on the frontend.

## Usage
1. Upload the plugin folder to your WordPress `wp-content/plugins/` directory and activate it.
2. Add Activities with day numbers, descriptions, and costs.
3. Add Clients with their contact details.
4. Create an Itinerary, select a client, and add activity rows (day + activity). Costs auto-sum in the editor.
5. Embed the itinerary on a page using the shortcode, replacing `123` with the itinerary post ID.
