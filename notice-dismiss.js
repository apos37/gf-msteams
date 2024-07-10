jQuery( document ).ready( function( $ ) {
    $( document ).on( 'click', '.gf-msteams-plugin-notice .notice-dismiss', function() {
        $.ajax({
            url: gf_teams_plugin_notice.ajax_url,
            type: 'POST',
            data: {
                action: 'dismiss_gf_msteams_plugin_notices',
                nonce: gf_teams_plugin_notice.nonce
            }
        });
    });
});