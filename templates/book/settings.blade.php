<div class="wrap">
    <h1><?php _e( 'LTI Settings', 'pressbooks-lti-provider') ?></h1>
    <hr class="wp-header-end">
    <p>
        <em><?php _e("Pressbooks will try to match the LTI User with their email. If, however, a matching Pressbooks user is not found then:", 'pressbooks-lti-provider') ?></em>
    </p>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-lti-provider-book' ) !!}
        <table class="form-table">
            @foreach ([
                'admin_default' => __('Map Administrator to the following Pressbooks role', 'pressbooks-lti-provider'),
                'staff_default' => __('Map Staff to the following Pressbooks role', 'pressbooks-lti-provider'),
                'learner_default' => __('Map Learner to the following Pressbooks role', 'pressbooks-lti-provider'),
            ] as $id => $label)
                <tr>
                    <th><label for="{{ $id }}">{{ $label }}</label></th>
                    <td><select name="{{ $id }}" id="{{ $id }}">
                            <option value="administrator" {!! selected( $options[$id], 'administrator' ) !!} ><?php _e('Administrator','pressbooks-lti-provider') ?></option>
                            <option value="editor" {!! selected( $options[$id], 'editor' ) !!} ><?php _e('Editor','pressbooks-lti-provider') ?></option>
                            <option value="author" {!! selected( $options[$id], 'author' ) !!} ><?php _e('Author','pressbooks-lti-provider') ?></option>
                            <option value="contributor" {!! selected( $options[$id], 'contributor' ) !!} ><?php _e('Contributor','pressbooks-lti-provider') ?></option>
                            <option value="subscriber" {!! selected( $options[$id], 'subscriber' ) !!} ><?php _e('Subscriber','pressbooks-lti-provider') ?></option>
                            <option value="anonymous" {!! selected( $options[$id], 'anonymous' ) !!} ><?php _e('Anonymous Guest','pressbooks-lti-provider') ?></option>
                        </select>
                    </td>
                </tr>
            @endforeach
        </table>
        <table class="form-table">
            <tr>
                <th><?php _e('Appearance', 'pressbooks-lti-provider') ?></th>
                <td>
                    <label><input name="hide_navigation" id="hide_navigation" type="radio"
                                  value="0" {!! checked( 0, $options['hide_navigation'] ) !!} /><?php _e('Display Pressbooks navigation elements in your LMS along with book content.', 'pressbooks-lti-provider') ?>
                    </label><br/>
                    <label><input name="hide_navigation" id="hide_navigation" type="radio"
                                  value="1" {!! checked( 1, $options['hide_navigation'] ) !!} /><?php _e('Display only book content in LMS.', 'pressbooks-lti-provider') ?></label>
                </td>
            </tr>
        </table>
        <h2><?php _e( 'Common Cartridge', 'pressbooks-lti-provider') ?></h2>
        <p>
            <em><?php _e("Export books as Common Cartridge files with LTI links.", 'pressbooks-lti-provider') ?></em>
        </p>
        <table class="form-table">
            <tr>
                <th><?php _e('Version', 'pressbooks-lti-provider') ?></th>
                <td>
                    <label><input name="cc_version" id="cc_version" type="radio"
                                  value="1.1" {!! checked( '1.1', $options['cc_version'] ) !!} /><?php _e('1.1', 'pressbooks-lti-provider') ?>
                    </label><br/>
                    <label><input name="cc_version" id="cc_version" type="radio"
                                  value="1.2" {!! checked( '1.2', $options['cc_version'] ) !!} /><?php _e('1.2', 'pressbooks-lti-provider') ?>
                    </label><br/>
                    <label><input name="cc_version" id="cc_version" type="radio"
                                  value="1.3" {!! checked( '1.3', $options['cc_version'] ) !!} /><?php _e('1.3', 'pressbooks-lti-provider') ?>
                    </label><br/>
                    <label><input name="cc_version" id="cc_version" type="radio"
                                  value="all" {!! checked( 'all', $options['cc_version'] ) !!} /><?php _e('Show all export versions', 'pressbooks-lti-provider') ?></label>
                </td>
            </tr>
        </table>
        {!! get_submit_button() !!}
    </form>
</div>
