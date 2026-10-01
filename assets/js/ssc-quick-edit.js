/**
 * Preselect the room's hotel in Quick Edit.
 *
 * The hotel column cell carries the current assignment in
 * data-ssc-hotel-id; copy it into the Quick Edit select when opened.
 */
(function ($) {
    if (typeof inlineEditPost === 'undefined' || !inlineEditPost.edit) {
        return;
    }
    var wp_inline_edit = inlineEditPost.edit;
    inlineEditPost.edit = function (id) {
        wp_inline_edit.apply(this, arguments);
        var post_id = 0;
        if (typeof id === 'object') {
            post_id = parseInt(this.getId(id), 10);
        }
        if (!post_id) {
            return;
        }
        var hotel_id = $('#post-' + post_id + ' .ssc-hotel-cell').data('ssc-hotel-id') || 0;
        $('select[name="ssc_room_hotel_id"]', '.inline-edit-row').val(hotel_id);
    };
})(jQuery);
