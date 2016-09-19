# Forge Sport Utilities
A utility plugin that works with existing plugins by patching their shortcomings in order to 'make the Forge Sport website work'.
## WordPress plugin dependencies
* WP Club Manager version >= 1.4.4
* Byline >= 0.25

## What does this plugin do?
* Removes useless metaboxes on the backend post creation user interface
that non-admin users don't need to see in order to reduce clutter. Specifically,
the metaboxes currently removed for non-admins are:
  * Custom Fields
  * Essential Grid Custom Options
  * Revolution Slider Options (the most useless one since non-admins can't even create new sliders...)

* Makes a custom query (available by calling the shortcode 'forge_print_results')
to utilise the niceties of the WP Club Manager plugin to manage sport scores,
whilst at the same time plugs (no puns intended) the limitations of the plugin
which only makes the results of one "home team" displayable.
  * _(since Version 3.3)_ an external link to the relevant match report can now be added via the match result post content

* Bootstrap the byline filter to run on the_author hook so that the correct author
name is displayed when they are added on the byline by an editor

* Dynamically fetches the data input from the banner post to display a banner reflecting UoS
BUCS performance 
