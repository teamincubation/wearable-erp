<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Attendance Register</h3>
        <p class="text-secondary m-0">Log employee daily attendance, shift context, and overtime hours</p>
    </div>
    <div class="d-flex">
        <a href="<?= base_url('company/hr/payroll') ?>" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="fa-solid fa-money-bill-wave me-1"></i> Salary Payroll Sheets
        </a>
        <?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                <i class="fa-solid fa-user-check me-1"></i> Log Daily Clock In
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-user-clock text-primary me-2"></i> Shift Logs Register</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Employee / Operator</th>
                        <th>Shift Date</th>
                        <th>Clock In / Out</th>
                        <th>Overtime Hours</th>
                        <th>Duty Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendance)): ?>
                        <?php foreach ($attendance as $att): ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($att['employee_name']) ?></strong></td>
                                <td><?= date('d M Y', strtotime($att['date'])) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace">
                                        <?= htmlspecialchars($att['clock_in'] ?: '--:--') ?>
                                    </span>
                                    <i class="fa-solid fa-arrow-right mx-1 small text-secondary"></i>
                                    <span class="badge bg-light text-dark font-monospace">
                                        <?= htmlspecialchars($att['clock_out'] ?: '--:--') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($att['overtime_hours'] > 0): ?>
                                        <span class="badge bg-warning text-dark font-monospace">+<?= number_format($att['overtime_hours'], 2) ?> hrs</span>
                                    <?php else: ?>
                                        <span class="text-secondary small">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($att['status'] === 'present') echo 'badge-success';
                                            elseif ($att['status'] === 'absent') echo 'badge-danger';
                                            elseif ($att['status'] === 'leave') echo 'badge-warning';
                                            else echo 'badge-secondary';
                                        ?>">
                                        <?= htmlspecialchars(ucfirst($att['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form action="<?= base_url('company/hr/attendance/delete/' . $att['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this attendance log?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-user-clock fs-1 mb-3 text-light"></i>
                                <p class="m-0">No attendance records logged for this month.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Attendance Modal -->
<?php if (\App\Core\Auth::hasPermission('company.users.create')): ?>
    <div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/hr/attendance/clock') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Log Employee Attendance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select text-dark" required>
                                <option value="">-- Choose Employee --</option>
                                <?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Duty Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select text-dark" required>
                                <option value="present">Present (Standard hours)</option>
                                <option value="absent">Absent</option>
                                <option value="leave">On Approved Leave</option>
                                <option value="holiday">Official Holiday</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Shift Schedule Mapping</label>
                            <select name="shift_id" class="form-select text-dark">
                                <option value="">-- Standard General (None) --</option>
                                <?php foreach ($shifts as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-start="<?= date('H:i', strtotime($s['start_time'])) ?>" data-end="<?= date('H:i', strtotime($s['end_time'])) ?>"><?= htmlspecialchars($s['name']) ?> (<?= date('h:i A', strtotime($s['start_time'])) ?> - <?= date('h:i A', strtotime($s['end_time'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Clock In Time</label>
                                <input type="time" name="clock_in" class="form-control text-dark" value="08:00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Clock Out Time</label>
                                <input type="time" name="clock_out" class="form-control text-dark" value="16:00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Save Attendance</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const shiftSelect = document.querySelector('select[name="shift_id"]');
        const clockInInput = document.querySelector('input[name="clock_in"]');
        const clockOutInput = document.querySelector('input[name="clock_out"]');
        const dateInput = document.querySelector('input[name="date"]');
        const gwh = <?= (int)($gwh ?? 8) ?>;
        const blockedDates = <?= json_encode($blocked_dates ?? []) ?>;

        if (shiftSelect && clockInInput && clockOutInput) {
            function updateClockTimes() {
                const selectedOption = shiftSelect.options[shiftSelect.selectedIndex];
                if (shiftSelect.value === "") {
                    // Standard General (None)
                    clockInInput.value = "08:00";
                    
                    // Calculate end time as 8:00 AM + GWH hours
                    let inHours = 8;
                    let outHours = (inHours + gwh) % 24;
                    let outHoursStr = String(outHours).padStart(2, '0');
                    clockOutInput.value = `${outHoursStr}:00`;
                } else {
                    // Custom shift schedule
                    const start = selectedOption.getAttribute('data-start');
                    const end = selectedOption.getAttribute('data-end');
                    if (start) clockInInput.value = start;
                    if (end) clockOutInput.value = end;
                }
            }

            shiftSelect.addEventListener('change', updateClockTimes);
            
            // Set initial value on setup
            updateClockTimes();
        }

        if (dateInput) {
            dateInput.addEventListener('change', function() {
                const selectedDate = this.value;
                if (blockedDates.includes(selectedDate)) {
                    alert("This date is configured as a Holiday or Weekend. Attendance cannot be logged on holidays or weekends.");
                    this.value = "";
                }
            });
            // Initial check on load
            if (blockedDates.includes(dateInput.value)) {
                dateInput.value = "";
            }
        }
    });
    </script>
<?php endif; ?>
