<?php
/**
 * Plugin Name:       Delete Post with Attachments
 * Plugin URI:        https://www.alsvin-tech.com/
 * Description:       A simple plugin to delete attached media files e.g. images/videos/documents, when the post is deleted. Supports Elementor, Divi Builder, Thrive Architect, Brizy and others Page Builders.
 * Version:           2.0
 * Requires at least: 4.1
 * Requires PHP:      5.6
 * Author:            Alsvin
 * Author URI:        https://profiles.wordpress.org/alsvin/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       alsvin-dpwa
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'Alsvin_Delete_Post_With_Attachments' ) ) {

    class Alsvin_Delete_Post_With_Attachments {

        public function __construct() {
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            add_action( 'before_delete_post', [ $this, 'handle_post_deletion' ] );
        }

        public function handle_post_deletion( $post_id ) {
            // Standard WordPress attachments
            $this->delete_standard_attachments( $post_id );

            // Elementor
            if ( is_plugin_active( 'elementor/elementor.php' ) ) {
                $this->delete_elementor_attachments( $post_id );
            }

            // Thrive Architect
            if ( is_plugin_active( 'thrive-visual-editor/thrive-visual-editor.php' ) ) {
                $this->delete_thrive_attachments( $post_id );
            }

            // Brizy
            if ( is_plugin_active( 'brizy/brizy.php' ) || is_plugin_active( 'brizy-pro/brizy-pro.php' ) ) {
                $this->delete_brizy_attachments( $post_id );
            }

            // Divi Builder (plugin or theme)
            if (
                is_plugin_active( 'divi-builder/divi-builder.php' ) ||
                strpos( get_template(), 'Divi' ) !== false
            ) {
                $this->delete_divi_attachments( $post_id );
            }
        }

        /**
         * Deletes media directly attached to the post
         */
        private function delete_standard_attachments( $post_id ) {
            $attachments = get_attached_media( '', $post_id );

            foreach ( $attachments as $attachment ) {
                $used_in = $this->get_posts_using_attachment( $attachment->ID );
                $is_direct_parent = ( $attachment->post_parent === $post_id );

                if ( $is_direct_parent ) {
                    $used_elsewhere = array_diff( array_merge( $used_in['content'], $used_in['thumbnail'] ), [ $post_id ] );

                    if ( ! empty( $used_elsewhere ) ) {
                        wp_update_post( [
                            'ID' => $attachment->ID,
                            'post_parent' => $used_elsewhere[0]
                        ] );
                    } else {
                        wp_delete_attachment( $attachment->ID, true );
                    }
                }
            }
        }

        /**
         * Detect and delete Elementor-based attachments
         */
        private function delete_elementor_attachments( $post_id ) {
            $elementor_data = get_post_meta( $post_id, '_elementor_data', true );

            if ( ! $elementor_data ) {
                return;
            }

            $data = json_decode( $elementor_data, true );

            if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
                return;
            }

            $media_ids = $this->extract_elementor_media_ids( $data );

            foreach ( $media_ids as $media_id ) {
                if ( get_post_type( $media_id ) !== 'attachment' ) {
                    continue;
                }

                $used_in = $this->get_posts_using_attachment( $media_id );
                $used_elsewhere = array_diff( array_merge( $used_in['content'], $used_in['thumbnail'] ), [ $post_id ] );

                if ( empty( $used_elsewhere ) ) {
                    wp_delete_attachment( $media_id, true );
                }
            }
        }

        /**
         * Detect and delete Thrive Architect-based attachments
         */
        private function delete_thrive_attachments( $post_id ) {
            $post = get_post( $post_id );

            if ( ! $post || empty( $post->post_content ) ) {
                return;
            }

            $content = $post->post_content;
            $media_ids = [];

            // Match Thrive shortcodes like [thrive_image id='123']
            if ( preg_match_all( '/\[thrive_[^\]]*?id=["\']?(\d+)["\']?\]/i', $content, $matches ) ) {
                $media_ids = array_merge( $media_ids, $matches[1] );
            }

            // Match data-id or data-media-id in HTML
            if ( preg_match_all( '/data-(?:media-)?id=["\'](\d+)["\']/i', $content, $matches ) ) {
                $media_ids = array_merge( $media_ids, $matches[1] );
            }

            // Match all URLs under wp-content/uploads/
            if ( preg_match_all( '/https?:\/\/[^"\']+\/wp-content\/uploads\/[^\s"\']+/i', $content, $url_matches ) ) {
                foreach ( $url_matches[0] as $url ) {
                    $attachment_id = attachment_url_to_postid( $url );
                    if ( $attachment_id ) {
                        $media_ids[] = $attachment_id;
                    }
                }
            }

            $media_ids = array_unique( array_map( 'intval', $media_ids ) );

            foreach ( $media_ids as $media_id ) {
                if ( get_post_type( $media_id ) !== 'attachment' ) {
                    continue;
                }

                $used_in = $this->get_posts_using_attachment( $media_id );
                $used_elsewhere = array_diff( array_merge( $used_in['content'], $used_in['thumbnail'] ), [ $post_id ] );

                if ( empty( $used_elsewhere ) ) {
                    wp_delete_attachment( $media_id, true );
                }
            }
        }

        /**
         * Recursively find media IDs in Elementor widget data
         */
        private function extract_elementor_media_ids( $data, &$media_ids = [] ) {
            if ( is_array( $data ) ) {
                foreach ( $data as $key => $value ) {
                    if ( is_array( $value ) || is_object( $value ) ) {
                        $this->extract_elementor_media_ids( $value, $media_ids );
                    } elseif ( in_array( $key, [ 'id', 'media_id' ], true ) && is_numeric( $value ) ) {
                        $media_ids[] = (int) $value;
                    }
                }
            }
            return array_unique( $media_ids );
        }

        private function delete_brizy_attachments( $post_id ) {
            $brizy_data = get_post_meta( $post_id, '_brizy_content', true );
            if ( empty( $brizy_data ) ) return;

            $data = json_decode( $brizy_data, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $data = maybe_unserialize( $brizy_data );
                if ( is_string( $data ) ) {
                    $data = json_decode( $data, true );
                }
            }
            if ( ! is_array( $data ) ) return;

            $uids = [];
            $media_urls = [];

            $iterator = new RecursiveIteratorIterator( new RecursiveArrayIterator( $data ) );
            foreach ( $iterator as $key => $value ) {
                if ( strtolower( $key ) === 'uid' || strtolower( $key ) === 'media' ) {
                    $uids[] = sanitize_text_field( $value );
                } elseif ( is_string( $value ) && $this->is_url_in_uploads( $value ) ) {
                    $media_urls[] = esc_url_raw( $value );
                }
            }

            $attachment_ids = [];

            global $wpdb;

            // Map UIDs to attachment IDs
            foreach ( array_unique( $uids ) as $uid ) {
                $attachment_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'brizy_attachment_uid' AND meta_value = %s",
                    $uid
                ) );
                if ( $attachment_id ) {
                    $attachment_ids[] = (int) $attachment_id;
                }
            }

            // Map URLs to attachment IDs
            foreach ( array_unique( $media_urls ) as $url ) {
                $aid = attachment_url_to_postid( $url );
                if ( $aid ) {
                    $attachment_ids[] = (int) $aid;
                }
            }

            $attachment_ids = array_unique( $attachment_ids );

            foreach ( $attachment_ids as $aid ) {
                if ( get_post_type( $aid ) !== 'attachment' ) continue;

                // Search all other posts’ _brizy_content meta for the same UID or URL
                $uid = get_post_meta( $aid, 'brizy_attachment_uid', true );
                $attachment_url = wp_get_attachment_url( $aid );

                $used_elsewhere = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta}
                     WHERE post_id != %d
                     AND meta_key = '_brizy_content'
                     AND (
                         meta_value LIKE %s
                         OR meta_value LIKE %s
                     )",
                    $post_id,
                    $wpdb->esc_like( $uid ) . '%',
                    '%' . $wpdb->esc_like( $attachment_url ) . '%'
                ) );

                if ( $used_elsewhere > 0 ) {
                    error_log( "Attachment {$aid} used in other Brizy pages. Not deleted." );
                    continue;
                }

                wp_delete_attachment( $aid, true );
            }
        }

        private function is_url_in_uploads( $string ) {
            if ( ! is_string( $string ) ) {
                return false;
            }
            $uploads = wp_upload_dir();
            return strpos( $string, $uploads['baseurl'] ) !== false;
        }

        /**
         * Deletes Divi-based media attachments only if not used elsewhere.
         */
        private function delete_divi_attachments( $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post || empty( $post->post_content ) ) {
                return;
            }

            // Initialize array to gather media IDs from this post
            $media_ids = [];

            // Use regex to find all wp-content/uploads URLs in content
            if ( preg_match_all( '/https?:\/\/[^"\']+\/wp-content\/uploads\/[^\s"\']+/i', $post->post_content, $matches ) ) {
                foreach ( array_unique( $matches[0] ) as $url ) {
                    $aid = attachment_url_to_postid( $url );
                    if ( $aid ) {
                        $media_ids[] = $aid;
                    }
                }
            }

            $media_ids = array_unique( array_map( 'intval', $media_ids ) );

            global $wpdb;
            foreach ( $media_ids as $aid ) {
                if ( get_post_type( $aid ) !== 'attachment' ) {
                    continue;
                }

                // Check usage in all other posts, including Divi content
                $count = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} p
                     JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                     WHERE p.ID != %d
                       AND pm.meta_key = '_et_pb_post_settings'
                       AND pm.meta_value LIKE %s",
                    $post_id,
                    '%' . $wpdb->esc_like( wp_get_attachment_url( $aid ) ) . '%'
                ) );

                // Also check within post_content of other posts
                if ( $count == 0 ) {
                    $other_count = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->posts}
                         WHERE ID != %d AND post_content LIKE %s",
                        $post_id,
                        '%' . $wpdb->esc_like( wp_get_attachment_url( $aid ) ) . '%'
                    ) );
                    $count = intval( $other_count );
                }

                if ( $count > 0 ) {
                    error_log( "Divi-safe: Attachment {$aid} is still in use elsewhere; not deleted." );
                    continue;
                }

                // If not used anywhere else, safe to delete
                wp_delete_attachment( $aid, true );
            }
        }

        /**
         * Check where the attachment is used (thumbnail, content, etc.)
         */
        private function get_posts_using_attachment( $attachment_id ) {
            $used_as_thumbnail = [];

            // Get posts using this as a featured image
            $query = new WP_Query( [
                'meta_key'       => '_thumbnail_id',
                'meta_value'     => $attachment_id,
                'post_type'      => 'any',
                'fields'         => 'ids',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
            ] );
            $used_as_thumbnail = $query->posts;

            // Get all URL variants (main + intermediate image sizes)
            $attachment_urls = [ wp_get_attachment_url( $attachment_id ) ];
            $meta = wp_get_attachment_metadata( $attachment_id );

            if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
                $upload_dir = wp_upload_dir();
                foreach ( $meta['sizes'] as $size ) {
                    if ( isset( $size['file'] ) ) {
                        $attachment_urls[] = trailingslashit( $upload_dir['baseurl'] ) . dirname( $meta['file'] ) . '/' . $size['file'];
                    }
                }
            }

            $used_in_content = [];

            foreach ( $attachment_urls as $url ) {
                if ( ! $url ) continue;

                $query = new WP_Query( [
                    's'              => esc_url_raw( $url ),
                    'post_type'      => 'any',
                    'fields'         => 'ids',
                    'post_status'    => 'any',
                    'posts_per_page' => -1,
                    'no_found_rows'  => true,
                ] );

                $used_in_content = array_merge( $used_in_content, $query->posts );
            }

            return [
                'thumbnail' => array_unique( $used_as_thumbnail ),
                'content'   => array_unique( $used_in_content ),
            ];
        }
    }

    new Alsvin_Delete_Post_With_Attachments();
}