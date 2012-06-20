# Carrington Build

## What is Carrington Build

Carrington Build is a drop in addition to the standard WordPress post and page edit screens. Carrington Build allows you to construct complex page layouts without having to know HTML or CSS. Layouts are based on the concept of breaking up the page content in to blocks of content that are arranged by row.

---


## Getting Started

- Start in WordPress
- Switch to Carrington Build
- Auto draft save
- Select build or template
- Content saves are immediate
- Editing other post properties works as normal
- Draft, publish & publish date all work as normal

---


## Rows

Carrington Build can add multiple rows of data to a post. Each row contains blocks. Different row types contain different block layouts. 

**To add a row to a layout:**

- Click on "Add row" in the Post Edit screen in the WordPress admin. If there are rows already in the layout then "Add row" will be at the bottom of that list of rows.
- Select a row type to add.
- The row will be added to the layout

**To remove a row from a layout:**

- Click the "(x)" at the top-left of the row box.
- A confirmation dialog box will appear
	- Click "Delete Row" to remove the row or "Cancel" to cancel.
- When a row is deleted all the content entered in any of its modules is deleted permanently as well.

**To reorder rows:**

- Drag the row by its "grabber", the dark shaded area along the left hand edge of the row, and drag it to its new position. The cursor will change when you enter this area to indicate that you can move the row.

### Default Rows

- Single Column
- Double Column
	- 50/50 split
	- 66/33 split
	- 33/66 split
	- column 1 float left
		- allows longer content on the right to flow under the item on the left
	- column 2 float right
		- allows longer content on the right to flow under the item on the right
- Triple Column
	- 33/33/33 split

---


## Modules

Carrington Build's rows contain blocks. Each block can contain a single module.

**To add a module to a block:**

- Click on "Add Module" at the bottom of the empty block
- In the popup box select the module type you'd like to add.
	- **Hint:** Use the toggle in the bottom right to switch between Icon and List view.
- Edit the module form when it loads.
- Click "Save" to save the text. Nothing is added to the post until "Save" is clicked.
- or Click "Cancel" to abort adding the module.
- **Hint:** Multiple modules can be added to the same block.

**To delete a module from a block:**

- Click "Delete" in the Module that you'd like to delete.
- A confirmation dialog box will appear.
	- Click "Delete Module" to remove the module or "Cancel" to cancel.
- When the module is deleted all its content is removed permanently.

**To edit a module:**

- Click "Edit" in the module to be edited to bring up the edit dialog.
- Modify the content as desired and click "Save" to commit the changes.

**To reorder a module:**

- When a row block contains multiple modules those modules can be reordered
- Click on a module to pick it up.
- Drop the module in its desired order in the block.

### Effects on Excerpts, Search & RSS

Each modules exports a plain text version of itself to the standard WordPress `post_content` for use Searching content. The `post_content` is also used to generate excerpts for archive pages and rss feeds. Modules that do not directly contain post content, for example sidebars and widgets, should not add their content to the `post_content`.

### Default Modules

- Plain Text
	- Standard plain text input
	- Raw input good if inserting JavaScript is needed
- Rich Text
	- Includes TinyMCE Rich Text Editor
	- Does not include all the features of the WordPress rich text editor
- HTML
	- Outputs HTML as input
	- Limited by the WordPress `unfiltered_html` privilege level
- Callout
	- Insert a title, content and link
	- Configure text & image sizes
- Post Callout
	- Select a post to use for the title, content and link destination
	- Configure text & image sizes as well as customize the content
- Hero
	- Similar to the Callout module
	- Output designed for full image backgrounds with overlaid text
	- Allows additional configuration of image position & box height
- Notice
	- Same input as the HTML module
	- Outputs div with class of `cfct-notice` for special styling
- Divider
	- Output a simple &lt;hr /&gt; element
- Header
	- Output a simple &lt;h# /&gt; element
	- Select the header level & insert text
- Image
	- Allows for the selection of an image from either the current post or the global gallery
	- Allows for selection of image size and link destination
	- If the current post has a featured image it will be highlighted in the "Post Images" list
- Loop
	- Define parameters for pulling a list of posts or pages for display in a list
	- Filter by post type, taxonomy, author
	- Configure number of items & output template
- Sub-Pages Loop
	- Output a loop of subpages of the current page
	- Configure output template, number of items & pagination link
- Shortcode
	- Define a shortcode to be run
	- Displays list of known shortcodes that can be processed
- Carousel
	- Allows for the selection of posts that have featured images to use in the creation of a carousel
	- Configure the image size, link inclusion, carousel height, navigation position, transition and transition duration
- Widget
	- Requires the new WordPress 2.7+ Widget format
- Sidebar
	- Auto Sidebar generation
	- The only way to use Pre WordPress 2.7 Widgets
- Pullquote
	- Designed and included to show the possibilities with module output

### Advanced Module Options

Modules support the addition of custom attributes. Custom module options are responsible for saving and using the saved data. If advanced module options are available there will be a cog icon on the right side of the header when editing a module.

### Module-Specific Instructions

Each Module can have specific descriptions or instructions made available directly within the Modules Options (popover) screen. If that module has inline documentation then simply click the 'Gear' icon in the top right and select "About&hellip;". If the "About&hellip;" menu item is not available then no inline documentation is available for that module.

The user option for displaying inline help automatically is set by default to "on". This can be turned of on a per user basis in the user profile screen.

---


## Admin Tab Behavior

Clicking on either the WordPress or Carrington Build tabs in the WordPress edit screen will set that content as active for that page or post. This is done immediately via ajax. To ensure search integrity the Build content is converted to a text only representation and saved to the Post Content. If you previously had content in your post or page that content is saved to a revision before it is cleared to make room for the plain text version of the build data. Build data, since it is saved as postmeta, is never cleared until you reset the layout or delete the page or post.

After switching between tabs and modifying content, wether you modified the content or not, it is best to save that content before you leave the screen to ensure the integrity of your site's content and, in the event that you're editing live data, that the proper content is displayed to your users.

---

<!-- hiding until support is better

## Templates

Carrington build has support for saving layouts as templates. These layouts contain the row data needed to reproduce the layout. Templates to not contain any module data.

**To save a template:**

- Once a post has been saved with a layout the layout can then be saved as a template. 
- Click on the Actions menu, the cog icon to the right of the Tabs, and select "Save Layout as Template".
- A dialog box will appear asking for a template name and description. Enter these values and click "Save" to commit the changes.

**To use a template:**

- Templates can be selected when starting a post. 
- When starting a Carrington Build layout click on "Choose a template".
- The template chooser will appear and display all available templates.
- Upon selecting a template the template's rows will be saved in to the current post.

-->