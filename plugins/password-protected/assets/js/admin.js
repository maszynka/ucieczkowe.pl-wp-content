jQuery( document ).ready(function( $ ) {

    toggle_recaptcha_version( $ );
    set_recaptcha_version_fields( $ );

    $( '.click-to-display-popup' ).on( 'click', function( e ) {
        let elementId = '#pro-popup';
        if ( $( elementId ).length ) {
            $( elementId ).remove();
        }

        let popupHtml = `<div id="pro-popup" style="">
            <div class="pp-container" style="">
                <a class="pp-close-button" style="">&times;</a>
                
                <div class="pp-body" style="">
                    <img style="" src="${passwordProtectedAdminObject.imageURL}cropped-logo.png" alt="Password Protected logo">
                
                    <p class="pp-description" style="">
                        ${passwordProtectedAdminObject.description}
                    </p>
                    <a class="pp-link" style="" href="#">${passwordProtectedAdminObject.buttonText}</a>
                </div>
                
                
            </div>
        </div>`;
        $( 'body' ).append( popupHtml );
    } );

    $( 'body' ).on( 'click', '.pp-close-button', function( e ) {
        $( '#pro-popup, #pro-upgrade-popup' ).remove();
    } );

    $( 'body' ).on( 'click', '.pp-link', function( e ) {
        e.preventDefault();
        window.location.href = $( '.pro-badge a' ).attr( 'href' );
    } );

    $( '.click-to-display-upgrade-popup' ).on( 'click', function( e ) {
        let element_id = '#pro-upgrade-popup';

        if ( $( element_id ).length ) {
            $( element_id ).remove();
        }

        let popup_html = `<div id="pro-upgrade-popup">
            <div class="pp-container" style="">
                <a class="pp-close-button" style="">&times;</a>
                
                <div class="pp-body" style="">
                    <img style="" src="${passwordProtectedAdminObject.imageURL}cropped-logo.png" alt="Password Protected logo">
                
                    <h2 style="font-weight: bolder;" class="pp-heading__title">
                        Get this and more advanced features with
                        <br>
                        Business Plan
                    </h2>
                    
                    <div style="margin-bottom: 20px;display: block;padding: 0 30px;" class="pp-description clearfix">
                        <div style="text-align: left; width: 50%;" class="pp-fl-left">
                            <ul>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Category / Taxonomy Protection
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Multiple Password Management
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    WP Admin Protection
                                </li>
                            </ul>
                        </div>
                        <div style="text-align: left; width: 50%;" class="pp-fl-right">
                            <ul>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Protection Screen Styling
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Multisite Network Support
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Request Password
                                </li>
                            </ul>
                        </div>
                    </div>

                    <a class="pp-pro-link" style="" href="https://passwordprotectedwp.com/pricing/?utm_source=plugin&utm_medium=business_pop_up&utm_campaign=plugin">Get Business Plan Now ></a>
                </div>
                
                
            </div>
        </div>`;

        $( 'body' ).append( popup_html );
    } );

} );

function set_recaptcha_version_fields( $ ) {
    var selected_version = $("input[name='password_protected_recaptcha[version]']:checked").val();
    if( selected_version == 'google_recaptcha_v2' ) {
        hide_recaptcha_v3_fields( $ );
    } else {
        hide_recaptcha_v2_fields( $ );
    }
}

function toggle_recaptcha_version( $ ) {
    $("input[name='password_protected_recaptcha[version]']").on('change', function() {
        if( $(this).val() === 'google_recaptcha_v2' ) {
            // hide v3 fields
            hide_recaptcha_v3_fields( $ );
            
            // show v2 fields
            $("#pp_google_recaptcha_v2_site_key").parent('div').fadeIn();
            $("#pp_google_recaptcha_v2_secret_key").parent('div').fadeIn();
            $("input[name='password_protected_recaptcha[v2_theme]']").parent('label').parent('td').parent('tr').fadeIn();
            
        } else {
            // show v3 fields
            $("#pp_google_recaptcha_v3_site_key").parent('div').fadeIn();
            $("#pp_google_recaptcha_v3_secret_key").parent('div').fadeIn();
            $("#pp_google_recpatcha_v3_score").parent('td').parent('tr').fadeIn();
            $("#pp_google_recpatcha_v3_badge").parent('td').parent('tr').fadeIn();
            
            // hide v2 fields
            hide_recaptcha_v2_fields( $ );
        }
    });
}

function hide_recaptcha_v2_fields( $ ) {
    $("#pp_google_recaptcha_v2_site_key").parent('div').hide();
    $("#pp_google_recaptcha_v2_secret_key").parent('div').hide();
    $("input[name='password_protected_recaptcha[v2_theme]']").parent('label').parent('td').parent('tr').hide();
}

function hide_recaptcha_v3_fields( $ ) {
    $("#pp_google_recaptcha_v3_site_key").parent('div').hide();
    $("#pp_google_recaptcha_v3_secret_key").parent('div').hide();
    $("#pp_google_recpatcha_v3_score").parent('td').parent('tr').hide();
    $("#pp_google_recpatcha_v3_badge").parent('td').parent('tr').hide();
}