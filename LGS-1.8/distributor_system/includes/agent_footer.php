      </div>
    </section>
  </main>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sidebar.js"></script>
  <?php if (isset($good_leads)): // Only for index.php ?>
  <script>
    window.dashboardData = {
      goodLeads: <?= $good_leads ?>,
      badLeads: <?= $bad_leads ?>,
      naLeads: <?= $na_leads ?>,
      performanceLabels: <?= json_encode($performance_labels) ?>,
      performanceData: <?= json_encode($performance_data) ?>
    };
  </script>
  <script src="../assets/js/agent_index.js"></script>
  <?php endif; ?>
  <script src="../assets/js/agent_table.js"></script>
</body>
</html>