=== Info HNews AI Image ===
Contributors: maxpowerbr,InfoHnews
Donate link: https://www.infohostingnews.com/thanks/
Tags: ai, featured image, image generator, content creation, artificial intelligence
Requires at least: 6.6
Tested up to: 6.8
Stable tag: 1.8
Requires PHP: 7.4
License: GPLv2
License URI: https://wordpress.org/about/gpl

Generate high-quality featured images for your posts using the power of free Artificial Intelligence.

== Description ==

Tired of searching for the perfect featured image for your posts?
**Info HNews AI Image** automates this process by generating unique, high-quality images directly within your WordPress dashboard using the power of Artificial Intelligence.
This plugin is free to use and provides a powerful starting point for automating your content's visual appeal.

**Features:**

* **Full Control:** On-demand image generation — one click on the "Generate Image" button does the job.
* **3 AI Models:** Use Pollinations.AI, HuggingFace.AI, and Stability.AI to create your images.
* **Flexibility:** Works both in the editor and directly from the post list (for any post type).
* **Smart Generation Sources:** Images can be created automatically from the article title, the post content, or a custom prompt.
* **Negative Prompts:** Refine the results by defining what you **do not** want to appear in the image.
* **Auto SEO:** Title and `alt` attributes are generated automatically to optimize your SEO.
* **Real-time Preview:** Thanks to Ajax technology, results are displayed instantly without saving the post.
* **Professional Quality:** Create eye-catching visuals in seconds.
* **Higher Engagement:** Attractive posts drive more clicks and shares.

Want even more power? **Check out the Pro version** with advanced features on our website:  
https://www.infohostingnews.com

== External Services ==

This plugin connects to third-party services to generate images via an API.
This functionality is essential for the plugin to work. The image generation process is only initiated when a user clicks the "Generate" button.

* **Hugging Face**
    * **Purpose:** Used to generate images based on text prompts via the Hugging Face Inference API.
    * **Data Sent:** The plugin sends the text prompt (derived from your post or custom input), negative prompts, configured image dimensions, and your private Hugging Face Access Token to the Hugging Face API for authentication and image creation.
    * **Service Links:** https://huggingface.co/terms-of-service and https://huggingface.co/privacy

* **Stability AI**
    * **Purpose:** Used to generate images based on text prompts via the Stability AI API.
    * **Data Sent:** The plugin sends the text prompt (derived from your post or custom input), negative prompts, configured image dimensions, and your private Stability AI API Key to their API for authentication and image creation.
    * **Service Links:** https://stability.ai/terms-of-use and https://stability.ai/privacy-policy

* **Pollinations.ai**
    * **Purpose:** A free service used to generate images based on text prompts.
    * **Data Sent:** The plugin sends the text prompt (derived from your post or custom input) and configured image dimensions as part of a URL request to the Pollinations.ai service.
      This service does not require an API key.
    * **Service Links:** https://pollinations.ai/

Links to specific Terms of Service and Privacy Policy are not provided by the service.

* **InfoHostingNews.com (Plugin Author's Website)**
    * **Purpose:** This plugin includes links on its settings page to the author's website for plugin information, documentation, professional version upsells, and a "Donate" link in the plugin's meta row.
    * **Data Sent:** No data is sent automatically. These are standard hyperlinks that a user must explicitly click to visit.
    * **Service Links:**  
      https://www.infohostingnews.com/infohnews/  
      https://www.infohostingnews.com/thanks/

== Installation ==

1. Upload the `info-hnews-ai-image` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'Info HNews AI Image' settings page in your dashboard to ensure Pollinations.ai is selected.
4. You can now generate images from the post edit screen (in the "AI Featured Image Generator" metabox) or directly from the posts list.

== Frequently Asked Questions ==

= Is the plugin really free? =

Yes!
This version of the plugin is fully free and functional.
We also offer a Pro version with additional features for advanced users.

= Which AI models can I use? =

You can choose between three different AI providers: Pollinations.AI, HuggingFace.AI, and Stability.AI.

= Does the plugin help with image SEO? =

Yes! It automatically generates the image Title and the Alternative Text (`alt` attribute), which are essential for SEO.

= Can I prevent certain objects or styles from appearing in the image? =

Yes, you can use the "Negative Prompts" field to specify what you don't want to see in the generated image, allowing for a more accurate result.

== Screenshots ==
1. AI Image Generator box inside the post editor with options for Pollinations, HuggingFace, and Stability.
2. Real-time preview window showing generated images before saving the post.
3. Plugin settings page with all available AI providers and configuration options.

== Changelog ==

= 1.8 =
Bug Fix

= 1.7 =
Code Syntax

= 1.6 =
Code Syntax

= 1.5 =
Code Syntax.

= 1.4 =
Code Syntax.

= 1.3 =
* **Bug Fix:** Replaced the image saving method (`wp_upload_bits`) with a more robust, GD-based method inspired by a working reference plugin.  
  This definitively resolves the persistent AJAX errors.  
* **Compliance:** The fix maintains compliance with WordPress.org standards by keeping file includes within their necessary functions.  
* **Cleanup:** Removed all lingering syntax errors from plugin files.

= 1.2 =
* Fixed fatal PHP syntax errors that caused AJAX calls to fail.
* Moved admin file includes into the functions that use them to comply with WordPress.org standards.

= 1.1 =
* Initial release.
