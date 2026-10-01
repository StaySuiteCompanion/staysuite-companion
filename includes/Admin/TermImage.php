<?php
/**
 * Featured photos for tablet taxonomies.
 *
 * Terms have no image field in the theme, so this adds a photo picker to
 * the add/edit screens and stores it in the theme's own option shape
 * (taxonomy_$id → category_attach_id), which the tablet renderer and
 * any theme code already read.
 *
 * @package StaySuite\Companion\Admin
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Admin;

use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Term photo picker for destination tablets.
 */
class TermImage {

    /**
     * Taxonomies carrying tablet photos.
     *
     * @var string[]
     */
    const TAXONOMIES = array( 'property_city', 'property_category', 'property_action_category', 'property_area' );

    /**
     * Wire up hooks.
     */
    public function __construct() {
        foreach ( self::TAXONOMIES as $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }
            add_action( "{$taxonomy}_add_form_fields", array( $this, 'add_field' ) );
            add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_field' ), 10, 2 );
            add_action( "created_{$taxonomy}", array( $this, 'save' ), 10, 2 );
            add_action( "edited_{$taxonomy}", array( $this, 'save' ), 10, 2 );
        }
        add_action( 'admin_enqueue_scripts', array( $this, 'media' ) );
    }

    /**
     * Load the media picker on term screens.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function media( $hook ) {
        if ( $hook !== 'term.php' && $hook !== 'edit-tags.php' ) {
            return;
        }
        wp_enqueue_media();
    }

    /**
     * Picker for the add-term form.
     *
     * @param string $taxonomy Current taxonomy slug.
     * @return void
     */
    public function add_field( $taxonomy ) {
        if ( ! in_array( $taxonomy, self::TAXONOMIES, true ) ) {
            return;
        }
        $this->picker( 0 );
    }

    /**
     * Picker for the edit-term form.
     *
     * @param WP_Term $term     Term being edited.
     * @param string  $taxonomy Current taxonomy slug.
     * @return void
     */
    public function edit_field( $term, $taxonomy ) {
        if ( ! in_array( $taxonomy, self::TAXONOMIES, true ) || ! ( $term instanceof WP_Term ) ) {
            return;
        }
        $this->picker( intval( $term->term_id ) );
    }

    /**
     * Render the photo picker row with preview + uploader.
     *
     * @param int $term_id Term ID, 0 on the add screen.
     * @return void
     */
    private function picker( $term_id ) {
        $attach_id = 0;
        $url = '';
        if ( $term_id > 0 ) {
            $data = get_option( 'taxonomy_' . $term_id );
            if ( is_array( $data ) && ! empty( $data['category_attach_id'] ) ) {
                $attach_id = intval( $data['category_attach_id'] );
                $src = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
                if ( is_array( $src ) ) {
                    $url = $src[0];
                }
            }
        }
        wp_nonce_field( 'ssc_term_image', 'ssc_term_image_nonce' );
        ?>
        <div class="form-field ssc-term-image-field">
            <label for="ssc_term_image_id"><?php esc_html_e( 'Featured photo', 'staysuite-companion' ); ?></label>
            <div class="ssc-term-image-preview" style="margin:6px 0;">
                <?php if ( $url !== '' ) : ?>
                    <img src="<?php echo esc_url( $url ); ?>" alt="" style="max-width:200px;height:auto;border-radius:8px;">
                <?php endif; ?>
            </div>
            <input type="hidden" id="ssc_term_image_id" name="ssc_term_image_id" value="<?php echo intval( $attach_id ); ?>">
            <button type="button" class="button ssc-term-image-pick"><?php esc_html_e( 'Pick photo', 'staysuite-companion' ); ?></button>
            <button type="button" class="button ssc-term-image-clear"><?php esc_html_e( 'Clear', 'staysuite-companion' ); ?></button>
            <p class="description"><?php esc_html_e( 'Shown on destination tablet cards. Falls back to the gradient when empty.', 'staysuite-companion' ); ?></p>
        </div>
        <script type="text/javascript">
        (function ($) {
            var frame = null;
            function field() {
                return $('#ssc_term_image_id');
            }
            $('.ssc-term-image-pick').on('click', function (e) {
                e.preventDefault();
                if (frame) {
                    frame.open();
                    return;
                }
                frame = wp.media({ title: 'Featured photo', multiple: false, library: { type: 'image' } });
                frame.on('select', function () {
                    var picked = frame.state().get('selection').first();
                    if (!picked) {
                        return;
                    }
                    field().val(picked.id);
                    var url = picked.attributes && picked.attributes.sizes && picked.attributes.sizes.thumbnail
                        ? picked.attributes.sizes.thumbnail.url
                        : picked.get('url');
                    $('.ssc-term-image-preview').html('<img src="' + url + '" alt="" style="max-width:200px;height:auto;border-radius:8px;">');
                });
                frame.open();
            });
            $('.ssc-term-image-clear').on('click', function (e) {
                e.preventDefault();
                field().val(0);
                $('.ssc-term-image-preview').empty();
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Save the picked photo into the theme's term option shape.
     *
     * @param int $term_id Term ID.
     * @param int $tt_id   Term taxonomy ID (unused).
     * @return void
     */
    public function save( $term_id, $tt_id ) {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by the term_edit_form action signature.
        unset( $tt_id );
        if ( ! isset( $_POST['ssc_term_image_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ssc_term_image_nonce'] ), 'ssc_term_image' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }
        $attach_id = isset( $_POST['ssc_term_image_id'] ) ? intval( $_POST['ssc_term_image_id'] ) : 0;
        if ( $attach_id > 0 && wp_attachment_is_image( $attach_id ) ) {
            $src = wp_get_attachment_image_src( $attach_id, 'medium' );
            $data = get_option( 'taxonomy_' . intval( $term_id ) );
            $data = is_array( $data ) ? $data : array();
            $data['category_attach_id'] = $attach_id;
            $data['category_featured_image'] = is_array( $src ) ? $src[0] : wp_get_attachment_url( $attach_id );
            update_option( 'taxonomy_' . intval( $term_id ), $data );
        } else {
            $data = get_option( 'taxonomy_' . intval( $term_id ) );
            if ( is_array( $data ) ) {
                unset( $data['category_attach_id'], $data['category_featured_image'] );
                update_option( 'taxonomy_' . intval( $term_id ), $data );
            }
        }
    }
}
