<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
use Joomunited\Queue\V1_0_0\JuMainQueue;

/**
 * Class JUQueueActions
 */
class JUQueueActions
{

    /**
     * All WPMF terms
     *
     * @var array
     */
    protected $terms = null;

    /**
     * JUQueueActions constructor.
     */
    public function __construct()
    {
        add_filter(
            'wpmf_physical_folders',
            function ($result, $datas, $element_id) {
                $result = JUQueueHelper::moveFile($datas);
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->updateQueuePostMeta((int)$datas['post_id'], (int)$element_id);
                return $result;
            },
            10,
            3
        );

        add_filter(
            'wpmf_replace_physical_url',
            function ($result, $datas, $element_id) {
                $result = JUQueueHelper::replacePhysicalUrl($result, $datas, $element_id);
                return $result;
            },
            10,
            3
        );

        /**
         * Add an input to allow changing file path
         */
        add_filter(
            'attachment_fields_to_edit',
            function ($form_fields, $post) {
                $url = wp_get_attachment_url($post->ID);

                $uploads = wp_upload_dir();

                if (strpos($url, $uploads['baseurl'])!==0) {
                    $html = __('This file is not in the allowed upload folder', 'wpmf');
                } else {
                    $path = str_replace($uploads['baseurl'], '', $url);

                    $file_extension = pathinfo($path, PATHINFO_EXTENSION);

                    $path = substr($path, 0, -(strlen($file_extension)+1));

                    $html = '<input name="attachments['.$post->ID.'][file_path]" id="attachments['.$post->ID.'][file_path]" value="'.htmlentities($path, ENT_COMPAT, 'UTF-8').'" /> . '.$file_extension;
                }

                $form_fields['file_path'] = array(
                    'label' => esc_html__('File path', 'wpmf'),
                    'input' => 'html',
                    'html' => $html,
                    'helps' => esc_html__('File path and name related to upload folder', 'wpmf') . '/' . substr($uploads['basedir'], strlen(get_home_path()))
                );

                return $form_fields;
            },
            10,
            2
        );

        /**
         * Save modification made on media page
         */
        add_filter(
            'attachment_fields_to_save',
            function ($post, $attachment) {
                if (isset($attachment['file_path'])) {
                    $datas = array(
                        'post_id' => $post['ID'],
                        'destination' => $attachment['file_path'],
                        'with_filename' => true,
                        'delete_folder' => false,
                        'update_database' => true,
                        'action' => 'wpmf_physical_folders'
                    );
                    $wpmfQueue = JuMainQueue::getInstance('wpmf');
                    $result = $wpmfQueue->addToQueue($datas);

                    if (is_wp_error($result)) {
                        $post['errors']['file_path']['errors'][] = $result->get_error_message();
                        return $post;
                    }
                }

                return $post;
            },
            10,
            2
        );

        /**
         * Hook on the set attachment folder action
         */
        add_action(
            'wpmf_attachment_set_folder',
            function ($attachment_id, $folder, $extra) {
                $update_db = true;
                if (is_array($extra) && !empty($extra['trigger']) && $extra['trigger'] === 'upload') {
                    $update_db = false;
                }

                $folders = JUQueueHelper::getParentTerms($folder);
                $destination = implode(DIRECTORY_SEPARATOR, $folders);
                $destination = apply_filters('wpmf_update_actual_folder_name', $destination);
                $datas = array(
                    'post_id' => $attachment_id,
                    'destination' => $destination,
                    'with_filename' => false,
                    'delete_folder' => false,
                    'update_database' => $update_db,
                    'action' => 'wpmf_physical_folders'
                );
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->addToQueue($datas);
                // Move files at the end of the script to avoid thumbnails generation issues
                add_action('shutdown', function () {
                    $wpmfQueue = JuMainQueue::getInstance('wpmf');
                    $wpmfQueue->proceedQueueAsync();
                });
            },
            10,
            3
        );

        /**
         * Hook on the add attachment action
         *
         * @todo : hook on wpmf_after_attachment_import to trigger the peoceedQueueAsync
         */
        add_action(
            'wpmf_add_attachment',
            function ($attachment_id, $folder_id) {
                $folders = JUQueueHelper::getParentTerms($folder_id);
                $destination = implode(DIRECTORY_SEPARATOR, $folders);
                $destination = apply_filters('wpmf_update_actual_folder_name', $destination);
                $datas = array(
                    'post_id' => $attachment_id,
                    'destination' => $destination,
                    'with_filename' => false,
                    'delete_folder' => false,
                    'update_database' => false,
                    'action' => 'wpmf_physical_folders'
                );
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->addToQueue($datas);
            },
            10,
            2
        );

        /**
         * Hook on the move folder action
         */
        add_action(
            'wpmf_move_folder',
            function ($folder_id, $destination_folder_id) {
                $term = get_term($destination_folder_id, WPMF_TAXO);
                JUQueueHelper::updateFolderName($folder_id, $term->name);
            },
            2,
            2
        );

        /**
         * Hook on the update folder name action
         */
        add_action(
            'wpmf_update_folder_name',
            function ($folder_id, $folder_name) {
                JUQueueHelper::updateFolderName($folder_id, $folder_name);
            },
            2,
            2
        );

        /**
         * Hook on the delete folder action
         */
        add_action(
            'wpmf_delete_folder',
            function ($folder_term) {
                JUQueueHelper::deleteFolder($folder_term);
            },
            2,
            2
        );

        /**
         * Ajax syncchonize folders
         */
        add_action('wp_ajax_wpmf_import_wpmf', function () {
            check_ajax_referer('wpmf_nonce', 'nonce');

            JUQueueHelper::updateFolderName(0, '');
            $wpmfQueue = JuMainQueue::getInstance('wpmf');
            $wpmfQueue->proceedQueueAsync();

            exit(0);
        });

        add_filter(
            'http_request_args',
            function ($r, $url) {
                if (is_array($r['body']) && !empty($r['body']['action']) && $r['body']['action'] === 'wp_async_wp_generate_attachment_metadata' && is_array($r['body']['metadata'])) {
                    unset($r['body']['metadata']);
                }
                return $r;
            },
            10,
            2
        );
    }
}
