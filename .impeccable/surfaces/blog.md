# Blog: blog.php and blog-post.php

Mode: Read.

Scope: Refine the existing green community journal. Preserve published copy, uploaded images, search, sorting, pagination, saved/mine views, the native writing disclosure, reactions and comments. Database queries and write handlers remain unchanged.

Direction: The user explicitly prefers a Reddit-style community feed. Use Be Vietnam Pro throughout, stacked white posts on a pale green-gray ground, author/date metadata first, compact right-hand thumbnails, up/down arrow controls, save and comment actions. Retain the existing moving article strip with pause, keyboard and reduced-motion behavior in a shorter introduction. Use existing plant photography only when a post has no uploaded cover.

Composition: A single post column at every width, with a topic/exploration sidebar to its right above 1050px. Below that, the sidebar follows the feed. Each post has one 12px bordered surface, a compact thumbnail, and 44px reaction targets. Search and sort retain persistent labels and personal views use underlined tabs. Article detail retains a readable 720px text column and contained uploaded images. Keep the page responsive from 320px upwards and show a direct recovery link for empty searches. Sidebar links use existing search and catalog/finder routes, with no invented membership counts or moderation rules.

Verification: Inspect real Apache-rendered listing and article pages, keyboard navigation, search/topics and pagination, mobile forms and menus, pause and reduced motion. The authenticated composer uses an explicit template fixture; mocked POST responses check vote/save/error feedback without writing to the database. These checks do not establish live write persistence. The backend integration suite requires dedicated setup credentials.
