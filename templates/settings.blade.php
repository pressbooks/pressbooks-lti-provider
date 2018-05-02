<div class="wrap">
    <h1>{{ __( 'LTI Settings', 'pressbooks-lti-provider') }}</h1>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-lti-provider' ) !!}
        <table class="form-table">
            <tr>
                <th><label for="whitelist">{{ __('Whitelist', 'pressbooks-lti-provider') }}</label></th>
                <td>
                    <textarea name="whitelist" id="whitelist" class="widefat" rows="10">{!! esc_textarea($options['whitelist']) !!}</textarea>
                    <p>
                        <em>{{ __("If you want to limit registrations to certain domains. One domain per line.", 'pressbooks-lti-provider') }}</em>
                    </p>
                </td>
            </tr>
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
                            <option value="anonymous" {!! selected( $options[$id], 'anonymous' ) !!} >{{ __('Anonymous Access','pressbooks-lti-provider') }}</option>
                        </select>
                    </td>
                </tr>
            @endforeach
        </table>
        {!! get_submit_button() !!}
    </form>
</div>