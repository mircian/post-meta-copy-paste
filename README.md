# Post Meta Copy Paste

WordPress plugin which adds the option to bulk copy-paste a post's meta.

## Getting Started

The plugin adds a simple metabox from which you can copy all of a post's meta. Paste that value in another post in the same metabox, check the "Update all meta?" checkbox and save/update.

The plugin won't make any changes to the meta values unless the checkbox is checked.

When updating all the meta, the plugin will remove all actions called on `save_post` to prevent other plugins overriding the values and it is suggested to run another "clean" update afterwards to allow plugins to run their actions.

## Filters

`pmcp_excluded_meta` allows you to exclude certain meta keys from this process, by default only WP Core keys are used here.

`pmcp_post_types` allows you to filter for which post types should the metabox be available.

`pmcp_capability` allows you to filter the capability needed to allow users to see and use this field. 
 
