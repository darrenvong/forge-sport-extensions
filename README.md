# Forge Sport Utilities

## Dependencies
* WP Club Manager version >= 1.4.4

## What does this plugin do?
Removes useless metaboxes on the backend post creation user interface
that non-admin users don't need to see in order to reduce clutter. Specifically,
the metaboxes currently removed for non-admins are:
* Custom Fields
* Essential Grid Custom Options
* Revolution Slider Options (the most useless one since non-admins can't even create new sliders...)

The plugin now also makes a custom query (available by calling the shortcode 'forge_print_results') to utilise the niceties of the WP Club Manager plugin to manage sport scores, whilst at the same time plugs (no puns intended) the limitations of the plugin which only makes the results of one "home team" displayable.
