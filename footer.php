<?php
// includes/footer.php
$user = getCurrentUser();
?>
  </div><!-- end page-content -->
</div><!-- end main-content -->
</div><!-- end app-layout -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="/assets/js/app.js"></script>
<?php if (isset($pageScripts)): ?>
<script>
<?= $pageScripts ?>
</script>
<?php endif; ?>
</body>
</html>
