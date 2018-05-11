=== Pressbooks LTI Provider ===
Contributors: conner_bw, greatislander
Donate link: https://opencollective.com/pressbooks/
Tags: pressbooks, lti, lms
Requires at least: 4.9.5
Tested up to: 4.9.5
Stable tag: 0.3.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin turns Pressbooks into an LTI provider.

== Description ==

[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-lti-provider.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-lti-provider) [![GitHub release](https://img.shields.io/github/release/pressbooks/pressbooks-lti-provider.svg?style=flat-square)](https://github.com/pressbooks/pressbooks-lti-provider/releases) [![Travis](https://img.shields.io/travis/pressbooks/pressbooks-lti-provider.svg?style=flat-square)](https://travis-ci.org/pressbooks/pressbooks-lti-provider/) [![Codecov](https://img.shields.io/codecov/c/github/pressbooks/pressbooks-lti-provider.svg?style=flat-square)](https://codecov.io/gh/pressbooks/pressbooks-lti-provider)

A plugin that turn Pressbooks into an [LTI Provider](https://en.wikipedia.org/wiki/Learning_Tools_Interoperability).

This plugin is built on top of an IMS Global Learning Consortium provided [LTI-Tool-Provider-Library-PHP](https://github.com/Izumi-kun/LTI-Tool-Provider-Library-PHP) abstraction
layer. It includes support for LTI 1.1 and the unofficial extensions to LTI 1.0, as well as the registration process and services of LTI 2.0.

== Installation ==

```
composer require pressbooks/pressbooks-lti-provider
```

Or, download the latest version from the releases page and unzip it into your WordPress plugin directory): https://github.com/pressbooks/pressbooks-lti-provider/releases

Then, activate and configure the plugin at the Network level.

== Troubleshooting ==

+ If the user's web browser does not allow 3rd Party Cookies, then logins will not work when Pressbooks is in an iframe.

+ This plugin requires [PDO for MySQL](http://php.net/manual/en/ref.pdo-mysql.php). These drivers are usually installed by default when installing MySQL packages for PHP. If you have
Pressbooks running, then PDO should already be installed. If for some reason the PDO drivers are missing, install them.

== Deep Linking ==

Connect URL (LTI 2 Registration URL)
> https://site/book/format/lti

Book cover page
> https://site/book/format/lti/launch

Post_id
> https://site/book/format/lti/launch/123

Post_type + Post_name
> https://site/book/format/lti/launch/front-matter/introduction

TODO?
> https://site/book/format/lti/launch/Hello%20World

> Candela style root URLs?

== ContentItemSelectionRequest ==

Pressbooks supports ContentItemSelectionRequest. This LTI feature allows course designers to select which chapter they want to display from inside their LMS. Setup instructions follow:

### Moodle
Do nothing. It just works.

### Canvas

Configure using the "Paste XML Configuration Type", making sure to replace the URLs with your own:

```
<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
  <blti:title>Pressbooks Book</blti:title>
  <blti:description>This is a Pressbooks Book</blti:description>
  <blti:launch_url>https://site/book/format/lti</blti:launch_url>
  <blti:extensions platform="canvas.instructure.com">
    <lticm:options name="link_selection">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url">https://site/book/format/lti</lticm:property>
    </lticm:options>
    <lticm:property name="selection_height">600</lticm:property>
    <lticm:property name="selection_width">800</lticm:property>
  </blti:extensions>
</cartridge_basiclti_link>
```

[More Info](https://github.com/instructure/canvas-lms/blob/master/doc/api/content_item.md)

== Screenshots ==

![Pressbooks LTI Consumers.](screenshot-1.png)
![Pressbooks LTI Settings.](screenshot-2.png)
![ContentItemSelectionRequest in Moodle.](screenshot-3.png)

== Changelog ==

= 0.3.0 =
* Prettier forms.
* Bug fixes for Canvas.

= 0.2.0 =
* Demo for Open source Slack channel.

= 0.1.0 =
* Initial scaffold.

== Upgrade Notice ==

Pressbooks LTI Provider requires Pressbooks >= 5.3.0 and WordPress >= 4.9.5.
