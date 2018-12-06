<html>
<body>
    <div id="ltiLaunchFormSubmitArea" style="display:none;">
        <form method="post" id="LtiRequestForm" name="LtiRequestForm" action="<?=$url?>" enctype="application/x-www-form-urlencoded">
            <?php foreach ($fields as $name => $value) { ?>
            <textarea name="<?=$name?>" value="<?=$value?>"><?=$value?></textarea>
            <?php } ?>
        </form>
    </div>
    <script language='JavaScript'>
        document.LtiRequestForm.submit();
    </script>
</body>
</html>
