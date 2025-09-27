<?php
// leads_modals.php
?>
<section class="table-components">
  <div class="container-fluid">

    <!-- Small inline styles for button alignment fix (keeps update isolated) -->
    <style>
      .action-buttons {
        display: inline-flex;
        gap: 0.5rem;
        align-items: center;
      }
      @media (max-width: 767.98px) {
        .d-flex.justify-content-between.align-items-center.mb-3 .action-buttons {
          align-self: flex-end;
        }
      }
      .action-buttons form { margin: 0; }

      /* Modal footer: keep stacked layout so Delete sits under Save */
      .modal-footer.stack {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        align-items: stretch;
      }
      .modal-footer.stack .primary-row {
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
      }
      .modal-footer.stack .delete-btn {
        width: auto;
        display: inline-block;
        align-self: flex-end;
        box-sizing: border-box;
      }
      @media (max-width: 575.98px) {
        .modal-footer.stack .delete-btn {
          width: 100%;
          align-self: stretch;
        }
      }
    </style>

    <div class="row mt-5">
      <div class="col-lg-12">
        <div class="card-style mb-30">
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="mb-3">Leads Monitor</h4>

              <!-- Filter Form -->
              <form class="row g-3 align-items-end mb-4" method="get">
                <input type="hidden" name="mode" value="<?= esc($mode) ?>">
                <div class="col-md-4">
                  <label class="form-label fw-bold">Agent</label>
                  <select name="agent_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Agent --</option>
                    <?php foreach ($agents as $a): ?>
                      <option value="<?= esc((string)$a['id']) ?>" <?= $selected_agent === (int)$a['id'] ? 'selected' : '' ?>>
                        <?= esc($a['username'] . ' — ' . $a['full_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-5">
                  <label class="form-label fw-bold">Date</label>
                  <div class="input-group">
                    <a class="btn btn-outline-secondary" href="?agent_id=<?= $selected_agent ?>&date=<?= esc(date('Y-m-d', strtotime($selected_date . ' -1 day'))) ?>&mode=<?= esc($mode) ?><?= $status_param ?>">← Prev</a>
                    <input type="date" name="date" value="<?= esc($selected_date) ?>" class="form-control" onchange="this.form.submit()">
                    <a class="btn btn-outline-secondary" href="?agent_id=<?= $selected_agent ?>&date=<?= esc(date('Y-m-d')) ?>&mode=<?= esc($mode) ?><?= $status_param ?>">Today</a>
                    <a class="btn btn-outline-secondary" href="?agent_id=<?= $selected_agent ?>&date=<?= esc(date('Y-m-d', strtotime($selected_date . ' +1 day'))) ?>&mode=<?= esc($mode) ?><?= $status_param ?>">Next →</a>
                  </div>
                </div>
              </form>

              <?php if ($selected_agent === 0): ?>
                <div class="alert alert-info">Please select an agent to view leads.</div>
              <?php else: ?>
                <?php
                  $display_filter = 'All';
                  if (is_array($status_filter) && !empty($status_filter)) {
                    $filter_count = count($status_filter);
                    if ($filter_count > 3) {
                      $display_filter = $filter_count . ' items';
                    } else {
                      $display_filter = implode(', ', $status_filter);
                    }
                  } elseif ($status_filter !== 'all') {
                    $display_filter = ucfirst($status_filter);
                  }
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0">Leads for <span class="text-primary"><?= esc($selected_date) ?></span></h5>

                    <!-- Dropdown Filter -->
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Filter: <?= esc($display_filter) ?>
                      </button>
                      <form class="status-filter-form" method="get">
                        <input type="hidden" name="agent_id" value="<?= $selected_agent ?>">
                        <input type="hidden" name="date" value="<?= esc($selected_date) ?>">
                        <input type="hidden" name="mode" value="<?= esc($mode) ?>">
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="statusFilterDropdown">
                          <li><a class="dropdown-item" href="?agent_id=<?= $selected_agent ?>&date=<?= esc($selected_date) ?>&mode=<?= esc($mode) ?>&status_filter=all">All</a></li>
                          <li><hr class="dropdown-divider"></li>
                          <?php foreach ($status_options as $s): ?>
                            <li>
                              <div class="form-check ms-3">
                                <input class="form-check-input status-checkbox" type="checkbox" name="status_filter[]" value="<?= esc($s) ?>" id="status_<?= esc(str_replace([' ', '-'], '_', $s)) ?>" <?= (is_array($status_filter) && in_array($s, $status_filter)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status_<?= esc(str_replace([' ', '-'], '_', $s)) ?>"><?= esc($s) ?></label>
                              </div>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </form>
                    </div>
                  </div>

                  <!-- RIGHT-SIDE ACTIONS: Show All + Delete All -->
                  <div class="action-buttons">
                    <a href="?agent_id=<?= $selected_agent ?>&date=<?= esc($selected_date) ?>&mode=<?= $mode === 'all' ? 'paged' : 'all' ?><?= $status_param ?>" class="btn btn-sm btn-outline-dark">
                      <?= $mode === 'all' ? 'Switch to Paginated' : 'Show All Leads' ?>
                    </a>

                    <?php if (!empty($leads)): ?>
                      <form method="post" onsubmit="return confirm('Delete ALL leads for <?= esc($selected_date) ?> for this agent? This action cannot be undone.');" class="m-0">
                        <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                        <input type="hidden" name="agent_id" value="<?= esc((string)$selected_agent) ?>">
                        <input type="hidden" name="lead_date" value="<?= esc($selected_date) ?>">
                        <button type="submit" name="delete_all" class="btn btn-sm btn-danger">Delete All</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if (empty($leads)): ?>
                  <div class="alert alert-info">No leads for this date.</div>
                <?php else: ?>
                  <?php
                    $status_classes = [
                        'Reviewed' => 'status-reviewed',
                        'Reviewed - Redesign' => 'status-redesign',
                        'Contacted - In Progress' => 'status-contacted',
                        'Pending - In Progress' => 'status-pending',
                        'Completed - Paid' => 'status-completed',
                        'Bad' => 'status-bad',
                    ];
                  ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>#</th>
                          <th>Company</th>
                          <th>Description</th>
                          <th>Status</th>
                          <th>Notes</th>
                          <th>Updated</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($leads as $l): ?>
                          <?php
                            $row_class = $status_classes[$l['status']] ?? '';
                            $modal_id = "editLeadModal" . (int)$l['id'];
                          ?>
                          <tr class="<?= $row_class ?>">
                            <td>
                              <a href="https://wa.me/<?= esc((string)$l['number']) ?>" target="_blank" class="text-decoration-none">
                                <?= esc((string)$l['number']) ?>
                              </a>
                            </td>
                            <td>
                              <a href="https://www.google.com/search?q=<?= urlencode($l['company_name']) ?>" target="_blank" class="text-decoration-none truncate" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= esc($l['company_name']) ?>">
                                <?= esc($l['company_name']) ?>
                              </a>
                            </td>
                            <td class="truncate" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= esc($l['description']) ?>"><?= esc(mb_strimwidth($l['description'],0,50,'...')) ?></td>
                            <td>
                              <form method="post" class="d-inline">
                                <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                  <?php foreach ($status_options as $s): ?>
                                    <option value="<?= esc($s) ?>" <?= $l['status'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                                  <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="update_lead" value="1">
                                <input type="hidden" name="agent_id" value="<?= esc((string)$selected_agent) ?>">
                              </form>
                            </td>
                            <td>
                              <form method="post" class="d-inline">
                                <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                                <input type="text" name="notes" class="form-control form-control-sm" value="<?= esc($l['notes']) ?>" onchange="this.form.submit()">
                                <input type="hidden" name="update_lead" value="1">
                                <input type="hidden" name="agent_id" value="<?= esc((string)$selected_agent) ?>">
                              </form>
                            </td>
                            <td><?= esc(date('H:i', strtotime($l['updated_at']))) ?></td>
                            <td><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= esc($modal_id) ?>">Edit</button></td>
                          </tr>

                          <!-- Edit Modal (no nested forms: update form and delete form are siblings) -->
                          <div class="modal fade" id="<?= esc($modal_id) ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                              <div class="modal-content">

                                <!-- UPDATE FORM (wraps inputs + Save) -->
                                <form method="post" class="update-form">
                                  <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Edit Lead #<?= esc((string)$l['number']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <div class="modal-body">
                                    <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                                    <input type="hidden" name="agent_id" value="<?= esc((string)$selected_agent) ?>">
                                    <div class="mb-3">
                                      <label class="form-label">Company Name</label>
                                      <input type="text" name="company_name" class="form-control" value="<?= esc($l['company_name']) ?>">
                                    </div>
                                    <div class="mb-3">
                                      <label class="form-label">Description</label>
                                      <textarea name="description" class="form-control" rows="3"><?= esc($l['description']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                      <label class="form-label">Status</label>
                                      <select name="status" class="form-select">
                                        <?php foreach ($status_options as $statusOption): ?>
                                          <option value="<?= esc($statusOption) ?>" <?= $l['status'] === $statusOption ? 'selected' : '' ?>><?= esc($statusOption) ?></option>
                                        <?php endforeach; ?>
                                      </select>
                                    </div>
                                    <div class="mb-3">
                                      <label class="form-label">Notes</label>
                                      <textarea name="notes" class="form-control" rows="3"><?= esc($l['notes']) ?></textarea>
                                    </div>
                                  </div>

                                  <!-- Save row (inside update form) -->
                                  <div class="modal-footer primary-row">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_lead" class="btn btn-primary">Save Changes</button>
                                  </div>
                                </form>

                                <!-- DELETE FORM (sibling, NOT nested) -->
                                <div class="modal-footer stack">
                                  <form method="post" onsubmit="return confirm('Are you sure you want to delete this lead? This action cannot be undone.');" class="m-0">
                                    <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                    <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                                    <input type="hidden" name="agent_id" value="<?= esc((string)$selected_agent) ?>">
                                    <button type="submit" name="delete_lead" class="btn btn-danger delete-btn">Delete Lead</button>
                                  </form>
                                </div>

                              </div>
                            </div>
                          </div>

                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <!-- Pagination -->
                  <?php if ($mode === 'paged' && $total_rows > $per_page): ?>
                    <?php $total_pages = ceil($total_rows / $per_page); ?>
                    <nav aria-label="Lead pagination">
                      <ul class="pagination justify-content-center">
                        <?php
                          $max_visible = 10;
                          $half_visible = floor($max_visible / 2);
                          $start = max(1, $page - $half_visible);
                          $end = min($total_pages, $start + $max_visible - 1);
                          if ($end - $start + 1 < $max_visible) {
                            $start = max(1, $end - $max_visible + 1);
                          }
                        ?>
                        <?php if ($page > 1): ?>
                          <li class="page-item"><a class="page-link" href="?agent_id=<?= $selected_agent ?>&date=<?= esc($selected_date) ?>&mode=paged&page=1<?= $status_param ?>">First</a></li>
                        <?php endif; ?>
                        <?php if ($page > 1): ?>
                          <li class="page-item"><a class="page-link" href="?agent_id=<?= $selected_agent ?>&date=<?= esc($selected_date) ?>&mode=paged&page=<?= $page - 1 ?><?= $status_param ?>">Prev</a></li>
                        <?php else: ?>
                          <li class="page-item disabled"><span class="page-link">Prev</span></li>
                        <?php endif; ?>
                        <?php for ($p = $start; $p <= $end; $p++): ?>
                          <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="?agent_id=<?= $selected_agent ?>&date=<?= esc($selected_date) ?>&mode=paged&page=<?= $p ?><?= $status_param ?>"><?= $p ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                          <li class="page-item"><a class="page-link" href="?agent_id=<?= $selected_agent ?>&date=<?= esc($selected_date) ?>&mode=paged&page=<?= $page + 1 ?><?= $status_param ?>">Next</a></li>
                        <?php else: ?>
                          <li class="page-item disabled"><span class="page-link">Next</span></li>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                          <li class="page-item"><a class="page-link" href="?agent_id=<?= $selected_agent ?>&date=<?= esc($selected_date) ?>&mode=paged&page=<?= $total_pages ?><?= $status_param ?>">Last</a></li>
                        <?php endif; ?>
                      </ul>
                    </nav>
                  <?php endif; ?>

                  <!-- Leads Distribution / Performance Overview (unchanged) -->
                  <?php if ($selected_agent > 0): ?>
                    <div class="card mt-4">
                      <div class="card-body">
                        <h5 class="mb-3">Leads Distribution</h5>
                        <div class="chart-container">
                          <canvas id="leadsPieChart"></canvas>
                        </div>
                        <div class="row g-3 mt-3">
                          <div class="col-md-3 col-sm-6 col-12"><div class="stat-card"><h3><?= $total_leads ?></h3><p>Total</p></div></div>
                          <?php foreach ($status_options as $status): ?>
                          <div class="col-md-3 col-sm-6 col-12"><div class="stat-card"><h3><?= $status_counts[$status] ?></h3><p><?= esc($status) ?></p></div></div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>

                    <div class="card mt-4">
                      <div class="card-body">
                        <h5 class="mb-3">Performance Overview</h5>
                        <div class="chart-container">
                          <canvas id="performanceChart"></canvas>
                        </div>
                        <div class="row g-3 mt-3">
                          <div class="col-3"><div class="stat-card"><h3><?= $average_per_day ?></h3><p>Avg / Day</p></div></div>
                          <div class="col-3"><div class="stat-card"><h3><?= $conversion_rate ?>%</h3><p>Conversion</p></div></div>
                          <div class="col-3"><div class="stat-card"><h3><?= $recent_leads ?></h3><p>Last 7 Days</p></div></div>
                          <div class="col-3"><div class="stat-card"><h3><?= $peak_count ?></h3><p>Peak Day</p></div></div>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              <?php endif; ?>

              <script src="../assets/js/Chart.min.js"></script>
              <script src="../assets/js/sidebar.js"></script>
              <script>
                // Charts (unchanged)...
                const ctxPie = document.getElementById('leadsPieChart')?.getContext('2d');
                if (ctxPie) {
                  new Chart(ctxPie, {
                    type: 'pie',
                    data: {
                      labels: <?= json_encode($status_options) ?>,
                      datasets: [{
                        data: <?= json_encode(array_values($status_counts)) ?>,
                        backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF']
                      }]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'top' } } }
                  });
                }

                const ctx = document.getElementById('performanceChart')?.getContext('2d');
                if (ctx) {
                  new Chart(ctx, {
                    type: 'line',
                    data: {
                      labels: <?= json_encode($performance_labels ?? []) ?>,
                      datasets: [{ label: 'Completed Leads', data: <?= json_encode($performance_data ?? []) ?>, borderColor: 'rgb(75, 192, 192)', tension: 0.1 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                  });
                }

                // Dropdown status filter submit
                document.addEventListener('DOMContentLoaded', function() {
                  const dropdownElement = document.getElementById('statusFilterDropdown');
                  const filterForm = document.querySelector('.status-filter-form');
                  let filterChanged = false;
                  const statusCheckboxes = document.querySelectorAll('.status-checkbox');
                  statusCheckboxes.forEach(checkbox => checkbox.addEventListener('change', () => filterChanged = true));
                  if (dropdownElement) {
                    dropdownElement.addEventListener('hidden.bs.dropdown', function () { if (filterChanged) filterForm.submit(); });
                  }

                  // ====== Keep Delete button size matched to the Save Changes button ======
                  const modalEls = document.querySelectorAll('.modal');
                  modalEls.forEach(modalEl => {
                    modalEl.addEventListener('shown.bs.modal', function () {
                      try {
                        const primaryBtn = modalEl.querySelector('.primary-row .btn-primary');
                        const deleteBtn = modalEl.querySelector('.delete-btn');
                        if (primaryBtn && deleteBtn) {
                          const rect = primaryBtn.getBoundingClientRect();
                          deleteBtn.style.width = rect.width + 'px';
                          deleteBtn.style.alignSelf = 'flex-end';
                        }
                      } catch (e) { console && console.warn && console.warn('modal sizing helper failed', e); }
                    });

                    modalEl.addEventListener('hidden.bs.modal', function () {
                      const deleteBtn = modalEl.querySelector('.delete-btn');
                      if (deleteBtn) { deleteBtn.style.width = ''; deleteBtn.style.alignSelf = ''; }
                    });
                  });

                  window.addEventListener('resize', function () {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                      const primary = openModal.querySelector('.primary-row .btn-primary');
                      const del = openModal.querySelector('.delete-btn');
                      if (primary && del) {
                        const r = primary.getBoundingClientRect();
                        del.style.width = r.width + 'px';
                      }
                    }
                  });
                  // ====== end sizing helper ======
                });
              </script>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
