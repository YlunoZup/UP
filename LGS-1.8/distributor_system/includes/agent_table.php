<?php
// agent_table.php
?>
<section class="table-components">
  <div class="container-fluid">
    <div class="row mt-5">
      <div class="col-lg-12">
        <div class="card-style mb-30">
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="mb-3">My Leads</h4>

              <!-- Filter Form for Date -->
              <form class="row g-3 align-items-end mb-4" method="get">
                <input type="hidden" name="mode" value="<?= esc($mode) ?>">
                <div class="col-md-5">
                  <label class="form-label fw-bold">Date</label>
                  <div class="input-group">
                    <a class="btn btn-outline-secondary" href="?date=<?= esc(date('Y-m-d', strtotime($selected_date . ' -1 day'))) ?>&mode=<?= esc($mode) ?><?= $status_param ?>">← Prev</a>
                    <input type="date" name="date" value="<?= esc($selected_date) ?>" class="form-control" onchange="this.form.submit()">
                    <a class="btn btn-outline-secondary" href="?date=<?= esc(date('Y-m-d')) ?>&mode=<?= esc($mode) ?><?= $status_param ?>">Today</a>
                    <a class="btn btn-outline-secondary" href="?date=<?= esc(date('Y-m-d', strtotime($selected_date . ' +1 day'))) ?>&mode=<?= esc($mode) ?><?= $status_param ?>">Next →</a>
                  </div>
                </div>
              </form>

              <?php
                $display_filter = 'All';
                $current_filter = in_array('all', $status_filter) ? ['all'] : $status_filter;
                if (is_array($current_filter) && !in_array('all', $current_filter) && !empty($current_filter)) {
                  $filter_count = count($current_filter);
                  if ($filter_count > 3) {
                    $display_filter = $filter_count . ' items';
                  } else {
                    $display_filter = implode(', ', $current_filter);
                  }
                } elseif (!in_array('all', $current_filter)) {
                  $display_filter = ucfirst($current_filter[0] ?? 'All');
                }
              ?>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                  <h5 class="mb-0">Leads for <span class="text-primary"><?= esc($selected_date) ?></span></h5>

                  <!-- Dropdown Filter -->
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                      Filter: <?= esc($display_filter) ?>
                    </button>
                    <form class="status-filter-form" method="get">
                      <input type="hidden" name="date" value="<?= esc($selected_date) ?>">
                      <input type="hidden" name="mode" value="<?= esc($mode) ?>">
                      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="statusFilterDropdown">
                        <li>
                          <div class="form-check ms-3">
                            <input class="form-check-input status-checkbox" type="checkbox" name="status_filter[]" value="all" id="status_all" <?= in_array('all', $current_filter) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="status_all">All</label>
                          </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($status_options as $s): ?>
                          <li>
                            <div class="form-check ms-3">
                              <input class="form-check-input status-checkbox" type="checkbox" name="status_filter[]" value="<?= esc($s) ?>" id="status_<?= esc(str_replace([' ', '-'], '_', $s)) ?>" <?= (in_array($s, $current_filter)) ? 'checked' : '' ?>>
                              <label class="form-check-label" for="status_<?= esc(str_replace([' ', '-'], '_', $s)) ?>"><?= esc($s) ?></label>
                            </div>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </form>
                  </div>
                </div>
                <a href="?date=<?= esc($selected_date) ?>&mode=<?= $mode === 'all' ? 'paged' : 'all' ?><?= $status_param ?>" class="btn btn-sm btn-outline-dark">
                  <?= $mode === 'all' ? 'Switch to Paginated' : 'Show All Leads' ?>
                </a>
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
                  $modals = '';
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
                          $modals .= '
                          <div class="modal fade" id="' . esc($modal_id) . '" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                              <div class="modal-content">
                                <form method="post">
                                  <input type="hidden" name="csrf" value="' . esc($csrf) . '">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Edit Lead #' . esc((string)$l['number']) . '</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <div class="modal-body">
                                    <input type="hidden" name="lead_id" value="' . esc((string)$l['id']) . '">
                                    <div class="mb-3"><label class="form-label">Company Name</label><input type="text" name="company_name" class="form-control" value="' . esc($l['company_name']) . '"></div>
                                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">' . esc($l['description']) . '</textarea></div>
                                    <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select">';
                          foreach ($status_options as $statusOption) {
                              $selectedAttr = ($l['status'] === $statusOption) ? ' selected' : '';
                              $modals .= '<option value="' . esc($statusOption) . '"' . $selectedAttr . '>' . esc($statusOption) . '</option>';
                          }
                          $modals .= '</select></div>
                                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3">' . esc($l['notes']) . '</textarea></div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_lead" class="btn btn-primary">Save Changes</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>';
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
                                  <option value="<?= $s ?>" <?= $l['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                              </select>
                              <input type="hidden" name="update_lead" value="1">
                            </form>
                          </td>
                          <td>
                            <form method="post" class="d-inline">
                              <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                              <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                              <input type="text" name="notes" class="form-control form-control-sm" value="<?= esc($l['notes']) ?>" onchange="this.form.submit()">
                              <input type="hidden" name="update_lead" value="1">
                            </form>
                          </td>
                          <td><?= esc(date('H:i', strtotime($l['updated_at']))) ?></td>
                          <td><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= esc($modal_id) ?>">Edit</button></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?= $modals ?>

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
                      <!-- First -->
                      <?php if ($page > 1): ?>
                        <li class="page-item">
                          <a class="page-link" href="?date=<?= esc($selected_date) ?>&mode=paged&page=1<?= $status_param ?>">First</a>
                        </li>
                      <?php endif; ?>
                      <!-- Prev -->
                      <?php if ($page > 1): ?>
                        <li class="page-item">
                          <a class="page-link" href="?date=<?= esc($selected_date) ?>&mode=paged&page=<?= $page - 1 ?><?= $status_param ?>">Prev</a>
                        </li>
                      <?php else: ?>
                        <li class="page-item disabled">
                          <span class="page-link">Prev</span>
                        </li>
                      <?php endif; ?>
                      <!-- Numbers -->
                      <?php for ($p = $start; $p <= $end; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                          <a class="page-link" href="?date=<?= esc($selected_date) ?>&mode=paged&page=<?= $p ?><?= $status_param ?>"><?= $p ?></a>
                        </li>
                      <?php endfor; ?>
                      <!-- Next -->
                      <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                          <a class="page-link" href="?date=<?= esc($selected_date) ?>&mode=paged&page=<?= $page + 1 ?><?= $status_param ?>">Next</a>
                        </li>
                      <?php else: ?>
                        <li class="page-item disabled">
                          <span class="page-link">Next</span>
                        </li>
                      <?php endif; ?>
                      <!-- Last -->
                      <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                          <a class="page-link" href="?date=<?= esc($selected_date) ?>&mode=paged&page=<?= $total_pages ?><?= $status_param ?>">Last</a>
                        </li>
                      <?php endif; ?>
                    </ul>
                  </nav>
                <?php endif; ?>
              <?php endif; ?>

              <script src="../assets/js/sidebar.js"></script>
              <script>
                // Handle status filter submit on dropdown close
                document.addEventListener('DOMContentLoaded', function() {
                  const dropdownButton = document.getElementById('statusFilterDropdown');
                  const dropdown = dropdownButton ? dropdownButton.closest('.dropdown') : null;
                  const filterForm = document.querySelector('.status-filter-form');
                  if (!dropdown || !filterForm) return;

                  let filterChanged = false;
                  const statusCheckboxes = dropdown.querySelectorAll('.status-checkbox');

                  statusCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                      filterChanged = true;
                      // If "All" checked -> uncheck everything else
                      if (checkbox.value === 'all' && checkbox.checked) {
                        statusCheckboxes.forEach(cb => { if (cb !== checkbox) cb.checked = false; });
                      } else if (checkbox.value !== 'all' && checkbox.checked) {
                        // If any other checked, uncheck 'all'
                        const allCb = dropdown.querySelector('input.status-checkbox[value="all"]');
                        if (allCb) allCb.checked = false;
                      }
                    });

                    // Prevent dropdown from closing due to click propagation
                    checkbox.addEventListener('click', (e) => {
                      e.stopPropagation();
                    });
                  });

                  // Also stop propagation for clicks inside the menu
                  const menu = dropdown.querySelector('.dropdown-menu');
                  if (menu) {
                    menu.addEventListener('click', (e) => {
                      e.stopPropagation();
                    });
                  }

                  // When dropdown is closed, submit if filters changed
                  dropdown.addEventListener('hidden.bs.dropdown', function () {
                    if (filterChanged) {
                      filterForm.submit();
                    }
                    filterChanged = false;
                  });
                });
              </script>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
