<div class="wrap">
    <h1>@if ($options['ID']) <?php _e( 'Editing', 'pressbooks-lti-provider') ?> @else <?php _e( 'Adding', 'pressbooks-lti-provider') ?> @endif <?php _e( 'LTI Consumer', 'pressbooks-lti-provider') ?></h1>
    <hr class="wp-header-end">
    <p><a href='{{ $back_url }}' rel='previous'><span aria-hidden='true'>&larr;</span> <?php _e( 'Back to LTI Consumers listing', 'pressbooks-lti-provider') ?> </a></p>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-lti-provider' ) !!}
        <input type="hidden" name="ID" value="{{ $options['ID'] }}"/>
        <table class="form-table">
            <tr>
                <th><label for="name"><?php _e('Name', 'pressbooks-lti-provider') ?></label></th>
                <td><input name="name" id="name" type="text" value="{{ $options['name'] }}" class="regular-text" required/></td>
            </tr>
            <tr>
                <th><label for="key"><?php _e('Key', 'pressbooks-lti-provider') ?></label></th>
                <td><input name="key" id="key" type="text" value="{{ $options['key'] }}" class="regular-text" required @if ($options['ID'])readonly="readonly"@endif/></td>
            </tr>
            <tr>
                <th><label for="secret"><?php _e('Secret', 'pressbooks-lti-provider') ?></label></th>
                <td><input name="secret" id="secret" type="text" value="{{ $options['secret'] }}" class="regular-text" required @if ($options['ID'])readonly="readonly"@endif/></td>
            </tr>
            <tr>
                <th><?php _e(' Enabled', 'pressbooks-cas-sso') ?></th>
                <td><label><input name="enabled" id="enabled" type="checkbox" value="1" {!! checked( $options['enabled'] ) !!}/></label></td>
            </tr>
            <tr>
                <th><label for="enable_from"><?php _e('Enable From', 'pressbooks-lti-provider') ?></label></th>
                <td><input name="enable_from" id="enable_from" type="date" value="{{ $options['enable_from'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="enable_until"><?php _e('Enable Until', 'pressbooks-lti-provider') ?></label></th>
                <td><input name="enable_until" id="enable_until" type="date" value="{{ $options['enable_until'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><?php _e(' Protected', 'pressbooks-cas-sso') ?></th>
                <td><label><input name="protected" id="protected" type="checkbox" value="1" {!! checked( $options['protected'] ) !!}/></label></td>
            </tr>
        </table>
        {!! get_submit_button() !!}
    </form>
</div>
