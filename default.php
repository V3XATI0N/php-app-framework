<?php
if (isset($oset['require_login']) and $oset['require_login'] === true and !isset($_SESSION['userdata']['userRank'])) {
    returnToHome();
}
openTemplate("default");
?>
<div style="text-align: center; padding: 36px; font-family: 'Fira Mono', monospace; font-size: 24pt;">
    D E F A U L T
</div>
<div style="padding: 36px; text-align: center; font-family: times new roman, times, serif; font-size: 14pt; font-weight: normal;">
    This is a the default page. Add plugins to make this application actually do something.
</div>
<div class="loading_place no-margin larger"></div>
<?php
closeTemplate("default");