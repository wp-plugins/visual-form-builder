=== Visual Form Builder ===
Contributors: mmuro
Tags: form, forms, form to email, email form, email, input, validation, jquery, shortcode
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 1.2

Dynamically build forms using a simple interface. Forms include jQuery validation and a basic logic-based verification system.  All form entries are stored in your WordPress database and can be managed using the dashboard.

== Description ==

*Visual Form Builder* is a plugin that allows you to build simple contact forms using an easy-to-use and familiar interface.

**Features**

* Setup and organize your form using a drag-and-drop interface
* Automatically includes a basic logic-based verification system
* Store form entries in your WordPress database and can manage them via the dashboard.
* Send form submissions to multiple emails
* Utilizes jQuery Form Validation
* Save time by adding a complete address block field
* Easy date fields using the jQuery UI Date Picker

== Installation ==

1. Upload `visual-form-builder` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create and configure a form (for help, refer to the FAQ or the Help on the plugin page)
1. Copy the form shortcode from the Shortcode box on the plugin page
1. Create a new page and add the shortcode to the content.

== Frequently Asked Questions ==

= How do I build my form? =

1. Click on the + tab, give your form a name and click Create Form.
1. Select form fields from the box on the left and click Create Field to add it to your form.
1. Edit the information for each form field by clicking on the down arrow.
1. Drag and drop the elements to put them in order.
1. Click Save Form to save your changes.

= What's the deal with the fieldsets? =

Fieldsets, a way to group form fields, are an essential piece of this plugin's HTML. As such, at least one fieldset is required and must be first in the order. Subsequent fieldsets may be placed wherever you would like to start your next grouping of fields.

= Can I use my own verification system such as a CAPTCHA? =

At this time, there is no alternative to the built-in anti-spam system.

= How do I customize the CSS? =

If you want to customize the appearance of the forms using your own CSS, here's how to do it:

1. Add this code to your theme's `functions.php` file: `add_filter( 'visual-form-builder-css', '__return_false' );`
1. Copy everything from `css/visual-form-builder.css` into your theme's `style.css`
1. Change the CSS properties in your theme's `style.css` as needed

If you want to customize the jQuery date picker CSS, follow these steps:

1. Add this code to your theme's `functions.php` file: `add_filter( 'vfb-date-picker-css', '__return_false' );`
1. Refer to the [jQuery UI Date Picker documentation on theming](http://jqueryui.com/demos/datepicker/#theming)

= How do I change the Date Picker configuration? =

The jQuery UI Date Picker is a complex and highly configurable plugin.  By default, Visual Form Builder's date field will use the default options and configuration.

To use the more complex features of the Date Picker plugin, you will need to:

1. Add a text field using Visual Form Builder
1. Save the form and use the shortcode to add it to a page
1. Use your browser to view the HTML source code
1. Find the text field you want to use and copy the ID (ex: start-date)
1. Using the above example ID, paste the following into your javascript file: `$( '#start-date' ).datepicker();`
1. Add and customize the [jQuery UI Date Picker configuration options](http://jqueryui.com/demos/datepicker)

== Screenshots ==

1. Visual Form Builder page
2. Configuring field item options
3. Entries management screen

== Changelog ==

**Version 1.2**

* Fix bug where reserved words may have been used
* Fix bug where multiple open validation dropdowns could not be used in the builder
* Add entries tracking and management feature
* Improve form submission by removing wp_redirect
* Add Sender Name and Email override

**Version 1.1**

* Fix bug that prevented all selected checkbox options from being submitted
* Add more help text on contextual Help tab
* Fix missing closing paragraph tag on success message

**Version 1.0**

* Plugin launch!