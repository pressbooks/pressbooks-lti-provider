<div class="wrap">
    <h1>{{ __( 'LTI Settings', 'pressbooks-lti-provider') }}</h1>
    <hr class="wp-header-end">
    <p>
        <em>{{ __("Pressbooks will try to match the LTI User with their email. If, however, a matching Pressbooks user is not found then:", 'pressbooks-lti-provider') }}</em>
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
                            <option value="administrator" {!! selected( $options[$id], 'administrator' ) !!} >{{ __('Administrator','pressbooks-lti-provider') }}</option>
                            <option value="editor" {!! selected( $options[$id], 'editor' ) !!} >{{ __('Editor','pressbooks-lti-provider') }}</option>
                            <option value="author" {!! selected( $options[$id], 'author' ) !!} >{{ __('Author','pressbooks-lti-provider') }}</option>
                            <option value="contributor" {!! selected( $options[$id], 'contributor' ) !!} >{{ __('Contributor','pressbooks-lti-provider') }}</option>
                            <option value="subscriber" {!! selected( $options[$id], 'subscriber' ) !!} >{{ __('Subscriber','pressbooks-lti-provider') }}</option>
                            <option value="anonymous" {!! selected( $options[$id], 'anonymous' ) !!} >{{ __('Anonymous Guest','pressbooks-lti-provider') }}</option>
                        </select>
                    </td>
                </tr>
            @endforeach
        </table>
        <table class="form-table">
            <tr>
                <th>{{ __('Appearance', 'pressbooks-lti-provider') }}</th>
                <td>
                    <label><input name="hide_navigation" id="hide_navigation" type="radio"
                                  value="0" {!! checked( 0, $options['hide_navigation'] ) !!} />{{ __('Display Pressbooks navigation elements in your LMS along with book content.', 'pressbooks-lti-provider') }}
                    </label><br/>
                    <label><input name="hide_navigation" id="hide_navigation" type="radio"
                                  value="1" {!! checked( 1, $options['hide_navigation'] ) !!} />{{ __('Display only book content in LMS.', 'pressbooks-lti-provider') }}</label>
                </td>
            </tr>
        </table>
        {!! get_submit_button() !!}
    </form>
</div>