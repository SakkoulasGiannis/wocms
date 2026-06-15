/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agents_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `intent` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_chat_messages_user_id_foreign` (`user_id`),
  CONSTRAINT `ai_chat_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_tool_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_tool_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `chat_message_id` bigint(20) unsigned DEFAULT NULL,
  `tool_name` varchar(255) NOT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `args` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`args`)),
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result`)),
  `undo_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`undo_payload`)),
  `confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `executed` tinyint(1) NOT NULL DEFAULT 0,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_tool_audits_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `ai_tool_audits_chat_message_id_index` (`chat_message_id`),
  KEY `ai_tool_audits_tool_name_index` (`tool_name`),
  CONSTRAINT `ai_tool_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_blog_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_blog_category` (
  `blog_id` bigint(20) unsigned NOT NULL,
  `blog_category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`blog_id`,`blog_category_id`),
  KEY `blog_blog_category_blog_category_id_index` (`blog_category_id`),
  CONSTRAINT `blog_blog_category_blog_category_id_foreign` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_blog_category_blog_id_foreign` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_blog_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_blog_tag` (
  `blog_id` bigint(20) unsigned NOT NULL,
  `blog_tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`blog_id`,`blog_tag_id`),
  KEY `blog_blog_tag_blog_tag_id_index` (`blog_tag_id`),
  CONSTRAINT `blog_blog_tag_blog_id_foreign` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_blog_tag_blog_tag_id_foreign` FOREIGN KEY (`blog_tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_slug_unique` (`slug`),
  KEY `blog_categories_parent_id_foreign` (`parent_id`),
  KEY `blog_categories_order_index` (`order`),
  KEY `blog_categories_is_active_index` (`is_active`),
  CONSTRAINT `blog_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `render_mode` varchar(50) NOT NULL DEFAULT 'full_page_grapejs',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'Status: active, draft, disabled',
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` text DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `tags_legacy` varchar(255) DEFAULT NULL,
  `published_at` varchar(255) DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL COMMENT 'Meta title (max 60-70 chars)',
  `seo_description` varchar(160) DEFAULT NULL COMMENT 'Meta description (max 155-160 chars)',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'Meta keywords (comma-separated)',
  `seo_canonical_url` varchar(255) DEFAULT NULL COMMENT 'Canonical URL',
  `seo_focus_keyword` varchar(255) DEFAULT NULL COMMENT 'Focus/target keyword',
  `seo_robots_index` varchar(20) NOT NULL DEFAULT 'index' COMMENT 'index or noindex',
  `seo_robots_follow` varchar(20) NOT NULL DEFAULT 'follow' COMMENT 'follow or nofollow',
  `seo_og_title` varchar(255) DEFAULT NULL COMMENT 'Open Graph title',
  `seo_og_description` text DEFAULT NULL COMMENT 'Open Graph description',
  `seo_og_image` varchar(255) DEFAULT NULL COMMENT 'Open Graph image URL',
  `seo_og_type` varchar(50) NOT NULL DEFAULT 'website' COMMENT 'Open Graph type (website, article, etc.)',
  `seo_og_url` varchar(255) DEFAULT NULL COMMENT 'Open Graph URL',
  `seo_twitter_card` varchar(50) NOT NULL DEFAULT 'summary_large_image' COMMENT 'Twitter card type',
  `seo_twitter_title` varchar(255) DEFAULT NULL COMMENT 'Twitter card title',
  `seo_twitter_description` text DEFAULT NULL COMMENT 'Twitter card description',
  `seo_twitter_image` varchar(255) DEFAULT NULL COMMENT 'Twitter card image URL',
  `seo_twitter_site` varchar(255) DEFAULT NULL COMMENT 'Twitter @username for site',
  `seo_twitter_creator` varchar(255) DEFAULT NULL COMMENT 'Twitter @username for creator',
  `seo_schema_type` varchar(50) DEFAULT NULL COMMENT 'Schema.org type (Article, BlogPosting, etc.)',
  `seo_schema_custom` text DEFAULT NULL COMMENT 'Custom JSON-LD schema markup',
  `seo_redirect_url` varchar(255) DEFAULT NULL COMMENT 'Redirect URL (301/302)',
  `seo_redirect_type` varchar(10) NOT NULL DEFAULT '301' COMMENT 'Redirect type (301 or 302)',
  `seo_sitemap_include` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Include in XML sitemap',
  `seo_sitemap_priority` varchar(10) NOT NULL DEFAULT '0.5' COMMENT 'Sitemap priority (0.0 to 1.0)',
  `seo_sitemap_changefreq` varchar(20) NOT NULL DEFAULT 'weekly' COMMENT 'Sitemap change frequency',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_tree` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `content_type` varchar(255) DEFAULT NULL,
  `content_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `url_path` varchar(255) NOT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `tree_path` varchar(255) NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `cache_enabled` tinyint(1) DEFAULT NULL COMMENT 'null = use template setting, true = force enable, false = force disable',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `page_layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`page_layout`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_tree_url_path_unique` (`url_path`),
  KEY `content_tree_template_id_foreign` (`template_id`),
  KEY `content_tree_parent_id_sort_order_index` (`parent_id`,`sort_order`),
  KEY `content_tree_url_path_index` (`url_path`),
  KEY `content_tree_content_type_content_id_index` (`content_type`,`content_id`),
  CONSTRAINT `content_tree_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `content_tree` (`id`) ON DELETE CASCADE,
  CONSTRAINT `content_tree_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `placeholder` text DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `help_text` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `validation_rules` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `order` int(11) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_fields_form_id_foreign` (`form_id`),
  CONSTRAINT `form_fields_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_spam` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submissions_form_id_foreign` (`form_id`),
  CONSTRAINT `form_submissions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `submit_button_text` varchar(255) NOT NULL DEFAULT 'Submit',
  `success_message` text DEFAULT NULL,
  `redirect_url` varchar(255) DEFAULT NULL,
  `send_email_notification` tinyint(1) NOT NULL DEFAULT 1,
  `notification_recipients` text DEFAULT NULL,
  `notification_subject` varchar(255) DEFAULT NULL,
  `notification_message` text DEFAULT NULL,
  `send_auto_reply` tinyint(1) NOT NULL DEFAULT 0,
  `auto_reply_email_field` varchar(255) DEFAULT NULL,
  `auto_reply_subject` varchar(255) DEFAULT NULL,
  `auto_reply_message` text DEFAULT NULL,
  `store_submissions` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `homes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `render_mode` varchar(50) NOT NULL DEFAULT 'full_page_grapejs',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'Status: active, draft, disabled',
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `body_css` longtext DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL COMMENT 'Meta title (max 60-70 chars)',
  `seo_description` varchar(160) DEFAULT NULL COMMENT 'Meta description (max 155-160 chars)',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'Meta keywords (comma-separated)',
  `seo_canonical_url` varchar(255) DEFAULT NULL COMMENT 'Canonical URL',
  `seo_focus_keyword` varchar(255) DEFAULT NULL COMMENT 'Focus/target keyword',
  `seo_robots_index` varchar(20) NOT NULL DEFAULT 'index' COMMENT 'index or noindex',
  `seo_robots_follow` varchar(20) NOT NULL DEFAULT 'follow' COMMENT 'follow or nofollow',
  `seo_og_title` varchar(255) DEFAULT NULL COMMENT 'Open Graph title',
  `seo_og_description` text DEFAULT NULL COMMENT 'Open Graph description',
  `seo_og_image` varchar(255) DEFAULT NULL COMMENT 'Open Graph image URL',
  `seo_og_type` varchar(50) NOT NULL DEFAULT 'website' COMMENT 'Open Graph type (website, article, etc.)',
  `seo_og_url` varchar(255) DEFAULT NULL COMMENT 'Open Graph URL',
  `seo_twitter_card` varchar(50) NOT NULL DEFAULT 'summary_large_image' COMMENT 'Twitter card type',
  `seo_twitter_title` varchar(255) DEFAULT NULL COMMENT 'Twitter card title',
  `seo_twitter_description` text DEFAULT NULL COMMENT 'Twitter card description',
  `seo_twitter_image` varchar(255) DEFAULT NULL COMMENT 'Twitter card image URL',
  `seo_twitter_site` varchar(255) DEFAULT NULL COMMENT 'Twitter @username for site',
  `seo_twitter_creator` varchar(255) DEFAULT NULL COMMENT 'Twitter @username for creator',
  `seo_schema_type` varchar(50) DEFAULT NULL COMMENT 'Schema.org type (Article, BlogPosting, etc.)',
  `seo_schema_custom` text DEFAULT NULL COMMENT 'Custom JSON-LD schema markup',
  `seo_redirect_url` varchar(255) DEFAULT NULL COMMENT 'Redirect URL (301/302)',
  `seo_redirect_type` varchar(10) NOT NULL DEFAULT '301' COMMENT 'Redirect type (301 or 302)',
  `seo_sitemap_include` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Include in XML sitemap',
  `seo_sitemap_priority` varchar(10) NOT NULL DEFAULT '0.5' COMMENT 'Sitemap priority (0.0 to 1.0)',
  `seo_sitemap_changefreq` varchar(20) NOT NULL DEFAULT 'weekly' COMMENT 'Sitemap change frequency',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `image_map_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_map_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `image_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_maps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `image_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_maps_slug_unique` (`slug`),
  KEY `image_maps_image_id_active_index` (`image_id`,`active`),
  CONSTRAINT `image_maps_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `image_map_images` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `image_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_sizes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `width` int(10) unsigned NOT NULL,
  `height` int(10) unsigned NOT NULL,
  `mode` enum('crop','fit','resize') NOT NULL DEFAULT 'crop',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_sizes_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `default_lat` decimal(12,2) DEFAULT NULL,
  `default_lng` decimal(12,2) DEFAULT NULL,
  `default_zoom` decimal(12,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `maps_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `uuid` uuid DEFAULT NULL,
  `collection_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `disk` varchar(255) NOT NULL,
  `conversions_disk` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `manipulations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`manipulations`)),
  `custom_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`custom_properties`)),
  `generated_conversions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`generated_conversions`)),
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responsive_images`)),
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'custom',
  `linkable_type` varchar(255) DEFAULT NULL,
  `linkable_id` bigint(20) unsigned DEFAULT NULL,
  `target` varchar(10) NOT NULL DEFAULT '_self',
  `icon` varchar(255) DEFAULT NULL,
  `css_class` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_parent_id_foreign` (`parent_id`),
  KEY `menu_items_menu_id_parent_id_order_index` (`menu_id`,`parent_id`,`order`),
  CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_slug_unique` (`slug`),
  KEY `menus_location_index` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_section_id` bigint(20) unsigned DEFAULT NULL,
  `section_template_id` bigint(20) unsigned DEFAULT NULL,
  `sectionable_type` varchar(255) DEFAULT NULL,
  `sectionable_id` bigint(20) unsigned DEFAULT NULL,
  `scope` varchar(32) DEFAULT NULL COMMENT 'listing | entry | null (default per-entity)',
  `section_type` varchar(255) NOT NULL,
  `edit_mode` enum('simple','advanced') NOT NULL DEFAULT 'simple',
  `name` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `rendered_html` text DEFAULT NULL,
  `css` longtext DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_sections_is_active_index` (`is_active`),
  KEY `page_sections_sectionable_type_sectionable_id_index` (`sectionable_type`,`sectionable_id`),
  KEY `page_sections_section_template_id_index` (`section_template_id`),
  KEY `page_sections_parent_section_id_foreign` (`parent_section_id`),
  KEY `page_sections_sectionable_scope_idx` (`sectionable_type`,`sectionable_id`,`scope`),
  CONSTRAINT `page_sections_parent_section_id_foreign` FOREIGN KEY (`parent_section_id`) REFERENCES `page_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_sections_section_template_id_foreign` FOREIGN KEY (`section_template_id`) REFERENCES `section_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `render_mode` varchar(50) NOT NULL DEFAULT 'full_page_grapejs',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'Status: active, draft, disabled',
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `body_css` longtext DEFAULT NULL,
  `featured_image` text DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL COMMENT 'Meta title (max 60-70 chars)',
  `seo_description` varchar(160) DEFAULT NULL COMMENT 'Meta description (max 155-160 chars)',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'Meta keywords (comma-separated)',
  `seo_canonical_url` varchar(255) DEFAULT NULL COMMENT 'Canonical URL',
  `seo_focus_keyword` varchar(255) DEFAULT NULL COMMENT 'Focus/target keyword',
  `seo_robots_index` varchar(20) NOT NULL DEFAULT 'index' COMMENT 'index or noindex',
  `seo_robots_follow` varchar(20) NOT NULL DEFAULT 'follow' COMMENT 'follow or nofollow',
  `seo_og_title` varchar(255) DEFAULT NULL COMMENT 'Open Graph title',
  `seo_og_description` text DEFAULT NULL COMMENT 'Open Graph description',
  `seo_og_image` varchar(255) DEFAULT NULL COMMENT 'Open Graph image URL',
  `seo_og_type` varchar(50) NOT NULL DEFAULT 'website' COMMENT 'Open Graph type (website, article, etc.)',
  `seo_og_url` varchar(255) DEFAULT NULL COMMENT 'Open Graph URL',
  `seo_twitter_card` varchar(50) NOT NULL DEFAULT 'summary_large_image' COMMENT 'Twitter card type',
  `seo_twitter_title` varchar(255) DEFAULT NULL COMMENT 'Twitter card title',
  `seo_twitter_description` text DEFAULT NULL COMMENT 'Twitter card description',
  `seo_twitter_image` varchar(255) DEFAULT NULL COMMENT 'Twitter card image URL',
  `seo_twitter_site` varchar(255) DEFAULT NULL COMMENT 'Twitter @username for site',
  `seo_twitter_creator` varchar(255) DEFAULT NULL COMMENT 'Twitter @username for creator',
  `seo_schema_type` varchar(50) DEFAULT NULL COMMENT 'Schema.org type (Article, BlogPosting, etc.)',
  `seo_schema_custom` text DEFAULT NULL COMMENT 'Custom JSON-LD schema markup',
  `seo_redirect_url` varchar(255) DEFAULT NULL COMMENT 'Redirect URL (301/302)',
  `seo_redirect_type` varchar(10) NOT NULL DEFAULT '301' COMMENT 'Redirect type (301 or 302)',
  `seo_sitemap_include` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Include in XML sitemap',
  `seo_sitemap_priority` varchar(10) NOT NULL DEFAULT '0.5' COMMENT 'Sitemap priority (0.0 to 1.0)',
  `seo_sitemap_changefreq` varchar(20) NOT NULL DEFAULT 'weekly' COMMENT 'Sitemap change frequency',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `render_mode` varchar(50) NOT NULL DEFAULT 'full_page_grapejs',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'Status: active, draft, disabled',
  `price` decimal(12,2) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `bedrooms` decimal(12,2) DEFAULT NULL,
  `bathrooms` decimal(12,2) DEFAULT NULL,
  `area` decimal(12,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `property_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `properties_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rental_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `property_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'for_rent',
  `price` decimal(12,2) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `bedrooms` decimal(12,2) DEFAULT NULL,
  `area` decimal(12,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `rental_properties_status_index` (`status`),
  KEY `rental_properties_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `section_template_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `section_template_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_template_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `placeholder` varchar(255) DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `section_template_fields_section_template_id_order_index` (`section_template_id`,`order`),
  CONSTRAINT `section_template_fields_section_template_id_foreign` FOREIGN KEY (`section_template_id`) REFERENCES `section_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `section_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `section_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'content',
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `html_template` text NOT NULL,
  `blade_file` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `default_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_templates_slug_unique` (`slug`),
  KEY `section_templates_category_is_active_index` (`category`,`is_active`),
  KEY `section_templates_is_system_index` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `render_mode` varchar(50) NOT NULL DEFAULT 'full_page_grapejs',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'Status: active, draft, disabled',
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sliders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sliders_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `slides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slider_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `button_text` varchar(255) DEFAULT NULL,
  `media_type` varchar(255) NOT NULL DEFAULT 'image',
  `video_url` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slides_slider_id_foreign` (`slider_id`),
  CONSTRAINT `slides_slider_id_foreign` FOREIGN KEY (`slider_id`) REFERENCES `sliders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `default_value` varchar(255) DEFAULT NULL,
  `adapts_to_render_mode` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_searchable` tinyint(1) NOT NULL DEFAULT 0,
  `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
  `is_url_identifier` tinyint(1) NOT NULL DEFAULT 0,
  `show_in_table` tinyint(1) NOT NULL DEFAULT 1,
  `column_position` enum('main','sidebar') NOT NULL DEFAULT 'main',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_fields_template_id_order_index` (`template_id`,`order`),
  CONSTRAINT `template_fields_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `use_slug_prefix` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If true, entries will use /template-slug/{entry-slug} format',
  `design_listing_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `design_entry_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `url_segment` varchar(255) DEFAULT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `model_class` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `has_physical_file` tinyint(1) NOT NULL DEFAULT 0,
  `requires_database` tinyint(1) NOT NULL DEFAULT 1,
  `has_seo` tinyint(1) NOT NULL DEFAULT 0,
  `file_path` varchar(255) DEFAULT NULL,
  `render_mode` enum('full_page_grapejs','sections','simple_content') NOT NULL DEFAULT 'full_page_grapejs' COMMENT 'How the template content is rendered',
  `html_content` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `enable_full_page_cache` tinyint(1) NOT NULL DEFAULT 0,
  `cache_ttl` int(11) NOT NULL DEFAULT 3600 COMMENT 'Cache TTL in seconds (default: 1 hour)',
  `is_public` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this template content is accessible from the frontend',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `tree_level` int(11) NOT NULL DEFAULT 0,
  `tree_path` varchar(500) DEFAULT NULL,
  `allow_children` tinyint(1) NOT NULL DEFAULT 1,
  `allow_new_pages` tinyint(1) NOT NULL DEFAULT 1,
  `allowed_parent_templates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_parent_templates`)),
  `allowed_child_templates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_child_templates`)),
  `use_custom_access` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_roles`)),
  `icon` text DEFAULT NULL,
  `show_in_menu` tinyint(1) NOT NULL DEFAULT 0,
  `menu_label` varchar(255) DEFAULT NULL,
  `menu_icon` text DEFAULT NULL,
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `templates_slug_unique` (`slug`),
  KEY `templates_slug_index` (`slug`),
  KEY `templates_is_active_index` (`is_active`),
  KEY `templates_parent_id_foreign` (`parent_id`),
  CONSTRAINT `templates_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_12_04_185721_create_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_12_04_185722_create_template_fields_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_12_04_191440_add_menu_settings_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_12_04_192108_add_table_name_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_12_04_201953_add_family_and_hierarchy_settings_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_12_05_054901_add_parent_id_and_is_system_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_12_05_083223_add_requires_database_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_12_05_083327_create_content_tree_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_12_05_102723_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_12_05_120227_add_show_in_table_to_template_fields_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_12_05_145426_add_is_public_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_12_05_210120_update_templates_menu_icon_to_text',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_12_05_212143_create_forms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_12_06_070647_update_templates_icon_to_text',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_12_06_083039_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_12_06_084900_create_ai_chat_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_12_06_185649_create_page_sections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_12_08_080144_create_image_sizes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_12_08_080515_create_media_models_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_12_08_120928_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_12_09_120458_add_render_mode_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_12_09_120516_improve_page_sections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_12_09_130247_create_section_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_12_09_130253_add_template_fields_to_page_sections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_12_09_130253_create_section_template_fields_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_12_10_042440_add_adapts_to_render_mode_to_template_fields_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_12_10_063452_rename_old_fields_to_new_standard',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_12_10_101838_update_existing_template_fields_adapts_flag',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_12_10_205256_add_has_seo_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_12_21_123034_add_field_options_to_template_fields_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_12_22_141304_add_use_slug_prefix_to_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_12_23_090725_add_status_to_pages_and_blogs_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_12_29_181528_add_column_position_to_template_fields_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_12_29_182021_add_enable_full_page_cache_to_templates_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_12_29_182103_add_cache_enabled_to_content_tree_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_03_04_000001_create_system_section_templates',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_03_04_000002_link_page_sections_to_templates',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_03_04_000003_cleanup_orphaned_sections',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_03_04_000004_drop_sections_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_03_04_000005_deactivate_sections_template',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_03_21_000001_create_sliders_and_slides_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_03_21_000001_create_image_map_images_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_03_21_000001_create_maps_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_03_21_000002_create_image_maps_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_03_21_000001_create_properties_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_03_21_000001_create_rental_properties_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_03_21_100001_create_menus_tables',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_03_26_200618_make_image_id_nullable_on_image_maps',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_03_27_071337_add_media_type_to_slides_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_03_27_074824_add_missing_columns_to_properties_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_03_29_000001_add_external_id_to_properties_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_03_29_000001_add_external_id_to_rental_properties_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_03_29_100001_add_missing_columns_to_rental_properties_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_03_29_201216_create_agents_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_04_07_121637_add_page_layout_to_content_tree',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_04_07_130321_add_parent_section_id_to_page_sections',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_04_08_100000_add_is_visible_to_page_sections',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_04_08_120000_create_primitive_section_templates',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_04_08_130000_add_is_default_to_content_tree',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_04_16_000001_add_settings_to_sliders_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_04_23_031515_create_ai_tool_audits_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_05_13_083806_alter_project_tables_size_columns_to_decimal',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_05_14_041221_create_entry_loop_section_template',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_05_14_090018_add_scope_to_page_sections',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_05_14_092306_add_missing_primitive_section_templates',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_05_14_130000_create_entry_gallery_section_templates',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_05_14_140000_add_sort_order_to_project_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_05_15_080000_add_design_flags_to_templates',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_05_15_100000_clean_primitive_div_template',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_05_15_110000_clean_primitive_heading_paragraph_templates',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_05_19_093500_add_body_field_to_project_templates',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_05_20_124000_create_blog_categories_and_tags',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_05_20_130000_migrate_legacy_blog_tags_to_pivot',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_05_21_090000_create_blog_loop_section_template',25);
