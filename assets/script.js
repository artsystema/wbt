document.addEventListener("DOMContentLoaded", () => {

    fetch("/api/reset_expired.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "global_reset=1"
    });

    let passcode = localStorage.getItem("passcode") || "";
    const authField = document.getElementById("authField");
    const authBtn = document.getElementById("authBtn");
    const authStatus = document.getElementById("authStatus");
    const historyBtn = document.getElementById("historyBtn");
    const taskList = document.getElementById("taskList");
    const taskListCompleted = document.getElementById("taskListCompleted");
    const bankDisplay = document.getElementById("bankDisplay");
    const filterStack = document.getElementById("activeFilters");
    const filters = [];
    renderFilters();

    function renderFilters() {
        filterStack.innerHTML = "";
        filters.forEach((f, i) => {
            const btn = document.createElement("button");
            btn.className = "filter-btn";
            btn.textContent = `${f.label} X`;
            btn.addEventListener("click", () => removeFilter(i));
            filterStack.appendChild(btn);
        });
        if (filters.length >= 2) {
            const reset = document.createElement("button");
            reset.className = "filter-reset";
            reset.textContent = "Reset";
            reset.addEventListener("click", () => {
                filters.length = 0;
                applyFilters();
                renderFilters();
            });
            filterStack.appendChild(reset);
        }
    }

    function applyFilters() {
        document.querySelectorAll('#taskList .task, #taskListCompleted .task').forEach(div => {
            if (div.classList.contains('header')) return;
            let show = true;
            filters.forEach(f => {
                if (f.type === 'category') {
                    const cats = (div.dataset.categories || '').split('|').filter(Boolean);
                    if (!cats.includes(f.value)) show = false;
                } else if (f.type === 'my') {
                    const owner = div.dataset.owner;
                    const status = div.dataset.status;
                    if (f.value === 'active' && !(owner === passcode && status === 'in_progress')) show = false;
                    if (f.value === 'submitted' && !(owner === passcode && status === 'pending_review')) show = false;
                    if (f.value === 'completed' && !(owner === passcode && status === 'completed')) show = false;
                }
            });
            div.style.display = show ? '' : 'none';
        });
    }

    function addFilter(type, value, label) {
        if (filters.some(f => f.type === type && f.value === value)) return;
        filters.push({ type, value, label });
        applyFilters();
        renderFilters();
    }

    function removeFilter(idx) {
        filters.splice(idx, 1);
        applyFilters();
        renderFilters();
    }

    function updateAuthDisplay() {
        if (passcode) {
            authField.style.display = "none";
            authBtn.style.display = "none";
            authStatus.innerHTML = `Authorized as [<strong>${passcode}</strong>] <button id="deauthBtn">Exit</button>`;
            //historyBtn.style.display = "inline";

            document.getElementById("deauthBtn").addEventListener("click", () => {
                localStorage.removeItem("passcode");
                passcode = "";
                authField.value = "";
                updateAuthDisplay();
            });

            fetch(`/api/user_stats.php?passcode=${encodeURIComponent(passcode)}`)
                .then(res => res.json())
                .then(stats => {
                    const parts = [];

                    //if (stats.paid_out > 0) parts.push(`[paid out: $${stats.paid_out}]`);
                    //if (stats.pending > 0) parts.push(`[pending: $${stats.pending}]`);

                    if (stats.active_jobs > 0 || stats.submitted_jobs > 0 || stats.completed_jobs > 0) {
                        const jobParts = [];
                        if (stats.active_jobs > 0) jobParts.push(`<span class="user-metric clickable" data-filter="active" title="Active jobs"><strong style="color:blue;">${stats.active_jobs}</strong></span>`);
                        if (stats.submitted_jobs > 0) jobParts.push(`<span class="user-metric clickable" data-filter="submitted" title="Pending review jobs"><strong style="color:#fbba00;">${stats.submitted_jobs}</strong></span>`);
                        if (stats.completed_jobs > 0) jobParts.push(`<span class="user-metric clickable" data-filter="completed" title="Completed jobs"><strong style="color:green;">${stats.completed_jobs}</strong></span>`);
                        parts.push(`[jobs: ${jobParts.join("|")}]`);
                    }




                    if (stats.last_submission) parts.push(`[last: ${new Date(stats.last_submission).toLocaleString()}]`);

                    const userMeta = parts.length ? `<span class="user-meta"> ${parts.join('')} </span>` : '';

                    let rankSpan = '';
                    if (stats.completed_jobs > 0 && stats.rank) {
                        const top = (stats.top10 || []).map((u, i) => `${i + 1}. ${u.assigned_to} ($${u.total})`).join('\n');
                        const coeffTitle = `${Math.round(stats.payout_coeff * 100)}% payout coefficient`;
                        rankSpan = `<span class="user-rank-meta">[<span class="user-rank" title="${top}">${stats.rank}</span><span class="user-star">★</span><span class="user-coeff" title="${coeffTitle}">${stats.payout_coeff.toFixed(2)}</span>]</span>`;
                    }

                    const histLink = `history.php?user=${encodeURIComponent(passcode)}`;
                    authStatus.innerHTML = `Authorized as [<strong><a href="${histLink}">${passcode}</a></strong>]${rankSpan}${userMeta} <button id="deauthBtn">Exit</button>`;

                    document.querySelectorAll('.user-metric.clickable').forEach(el => {
                        el.addEventListener('click', () => {
                            const filter = el.dataset.filter;
                            const label = `My ${filter}`;
                            addFilter('my', filter, label);
                        });
                    });

                    document.getElementById("deauthBtn").addEventListener("click", () => {
                        localStorage.removeItem("passcode");
                        passcode = "";
                        authField.value = "";
                        updateAuthDisplay();
                    });
                });






        } else {
            authField.style.display = "inline";
            authBtn.style.display = "inline";
            authStatus.innerHTML = "";
            historyBtn.style.display = "none";
        }

        fetchTasks();
    }

    authBtn.addEventListener("click", () => {
        const val = authField.value.trim();
        if (!val) return alert("Enter a passcode first.");
        passcode = val;
        localStorage.setItem("passcode", passcode);
        updateAuthDisplay();
    });

    updateAuthDisplay();





    function fetchTasks() {
        const tasksUrl = passcode ? `/api/tasks.php?passcode=${encodeURIComponent(passcode)}` : "/api/tasks.php";
        fetch(tasksUrl)
            .then(res => res.json())
            .then(tasks => {
                taskList.innerHTML = "";
                taskListCompleted.innerHTML = "";
                taskListCompleted.style.display = "none";

                const header = document.createElement("div");
                header.className = "task header";
                header.innerHTML = `
                    <div>Title</div>
                    <div>Description</div>
                    <div>Time</div>
                    <div>Reward</div>
                    <div>Status</div>
                    <div>Action</div>
                `;
                taskList.appendChild(header);

                if (tasks.length === 0) {
                    const emptyRow = document.createElement("div");
                    emptyRow.className = "task";
                    emptyRow.innerHTML = "<div>No tasks available</div>";
                    taskList.appendChild(emptyRow);
                    return;
                }

                let availableCount = 0;
                let inProgressCount = 0;

                tasks.forEach(task => {
                    if (task.status === "available") availableCount++;
                    if (task.status === "in_progress") inProgressCount++;
                });

                document.getElementById("taskStats").innerHTML = `[<span style="color:green;"><strong>${availableCount}</strong></span> available, ${inProgressCount} in progress]`;

                tasks.forEach(task => {
                const div = document.createElement("div");
                div.className = "task";
                div.dataset.id = task.id;
                div.className = `task ${task.status}`;
                div.dataset.owner = task.assigned_to || '';
                div.dataset.status = task.status;
                const categories = (task.category || '').split(',').map(c => c.trim()).filter(Boolean);
                div.dataset.categories = categories.join('|');

                    const isOwner = passcode && passcode === task.assigned_to;
                    const isInProgress = task.status === "in_progress";
                    const isPending = task.status === "pending_review";

                    let statusDisplay = task.status;
                    let actionHTML = "";

                    if (task.status === "in_progress") {
                        if (isOwner && task.start_time) {
                            const countdown = document.createElement("span");
                            countdown.className = "countdown";
                            const endTime = new Date(task.start_time).getTime() + task.estimated_minutes * 60000;
                            const estimatedMs = task.estimated_minutes * 60000;

                            countdown.dataset.end = new Date(endTime).toISOString();
                            countdown.dataset.estimatedMs = estimatedMs;
                            statusDisplay = countdown.outerHTML;
                        } else {
                            statusDisplay = "<span>in progress</span>";
                        }
                    }

                    if (!isInProgress) {
                        if (task.status === "available") {
                            actionHTML = `<button data-id="${task.id}">Take</button>`;
                        } else if (isPending && isOwner && task.comment) {
                            actionHTML = `<em class="task-comment">${escapeHtml(task.comment)}</em>`;
                        }
                    } else if (isOwner) {
                        actionHTML = `<form class="uploadForm" data-id="${task.id}" enctype="multipart/form-data" style="display:inline;">
                  <input type="file" name="attachment" required />
                  <button type="submit">Submit</button>
              </form>
              <form class="quitForm" data-id="${task.id}" style="display:inline;">
                  <button type="submit" class="quitBtn">Quit</button>
              </form>
              <input type="text" placeholder="Note (optional)" class="noteField" />`;

                    }

                    const catSpans = categories.map(c => `<span class="task-category clickable">${c}</span>`).join(', ');

                    div.innerHTML = `

          <div>
            <div><strong>${task.pinned ? '<span class="pinned-icon" title="Pinned">📌</span>' : ''}<a href="task.php?id=${task.id}">[${task.id}] ${task.title}</a></strong></div>
            <div class="task-meta">Posted on ${new Date(task.date_posted).toLocaleString()}</div>
            ${catSpans}
          </div>
          <div>
  ${task.description}
  ${
                        task.attachments && Array.isArray(task.attachments) && task.attachments.length > 0
                            ? `<div class="attachments"><strong>Attachments:</strong><ul>` +
                        (task.attachments || []).map(file => {
                            const name = file.split("/").pop();
                            const ext = name.split('.').pop().toLowerCase();
                            const supported = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'mp4', 'mp3', 'txt'];

                            const safeId = `preview-${task.id}-${name.replace(/[^a-z0-9]/gi, '_')}`;
                            let previewBtn = '';
                            let previewContainer = '';

                            if (supported.includes(ext)) {
                                previewBtn = `<button type="button" class="preview-toggle" data-src="${file}" data-type="${ext}" data-target="${safeId}">Preview</button>`;
                                previewContainer = `<div class="preview-container" id="${safeId}" style="display:none;"></div>`;
                            }

                            return `<li>
        <a href="${file}" target="_blank">${name}</a>
        ${previewBtn}
        ${previewContainer}
    </li>`;
                        }).join("") +`</ul></div>`  : ""}
</div>
          <div>${formatTime(task.estimated_minutes)}</div>
          <div>$${task.reward}</div>
          <div>${statusDisplay}</div>
          <div>${actionHTML}</div>
          `;
                    div.querySelectorAll('.task-category').forEach(catEl => {
                        if (catEl.textContent.trim()) {
                            catEl.addEventListener('click', () => {
                                addFilter('category', catEl.textContent.trim(), catEl.textContent.trim());
                            });
                        }
                    });

                    div.querySelectorAll(".preview-toggle").forEach(btn => {
                        btn.addEventListener("click", () => {
                            const container = div.querySelector(`#${btn.dataset.target}`);
                            const src = btn.dataset.src;
                            const type = btn.dataset.type;

                            const isOpen = container.style.display === "block";
                            container.style.display = isOpen ? "none" : "block";
                            btn.textContent = isOpen ? "Preview" : "Hide";

                            if (isOpen || container.innerHTML.trim()) return;

                            let html = '';
                            if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(type)) {
                                html = `<img src="${src}" style="max-width:100%; max-height:300px;">`;
                            } else if (type === 'mp4') {
                                html = `<video src="${src}" controls style="max-width:100%; max-height:300px;"></video>`;
                            } else if (type === 'mp3') {
                                html = `<audio src="${src}" controls></audio>`;
                            } else if (type === 'txt') {
                                fetch(src).then(res => res.text()).then(text => {
                                    container.innerHTML = `<pre style="white-space: pre-wrap; overflow:auto;">${text}</pre>`;
                                });
                                return;
                            }

                            container.innerHTML = html;
                        });
                    });

                    if (!isInProgress) {
                        const takeBtn = Array.from(div.querySelectorAll("button")).find(b => b.textContent === "Take");
                        if (takeBtn) {
                            takeBtn.addEventListener("click", (e) => {
                                e.stopPropagation();
                                if (!passcode) return alert("Enter passcode first.");
                                fetch("/api/tasks.php", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify({ task_id: task.id, passcode })
                                })
                                    .then(res => res.json())
                                    .then(result => {
                                        if (result.success) location.reload();
                                        else alert("Error: " + (result.error || "Unknown"));
                                    });
                            });
                        }

                    }

                    const form = div.querySelector(".uploadForm");
                    if (form) {
                        form.addEventListener("submit", e => {
                            e.preventDefault();
                            const fileInput = form.querySelector("input[name=attachment]");
                            const noteField = div.querySelector(".noteField");
                            const formData = new FormData();
                            formData.append("attachment", fileInput.files[0]);
                            formData.append("task_id", task.id);
                            formData.append("passcode", passcode);

                            if (noteField) formData.append("comment", noteField.value);

                            fetch("/api/submit.php", {
                                method: "POST",
                                body: formData
                            })
                                .then(res => res.json())
                                .then(result => {
                                    if (result.success) {
                                        alert("Submitted successfully!");
                                        location.reload();
                                    } else {
                                        alert("Upload failed: " + (result.error || "Unknown"));
                                    }
                                });
                        });

                        const quitForm = div.querySelector(".quitForm");
                        if (quitForm) {
                            quitForm.addEventListener("submit", e => {
                                e.preventDefault();
                                if (!confirm("Are you sure you want to quit this task?")) return;

                                const noteField = div.querySelector(".noteField");
                                const comment = noteField ? noteField.value : "";
                                fetch("/api/quit_task.php", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify({ task_id: task.id, passcode, comment })
                                })
                                    .then(res => res.json())
                                    .then(result => {
                                        if (result.success) {
                                            alert("Task has been quit and relisted.");
                                            location.reload();
                                        } else {
                                            alert("Error: " + (result.error || "Unknown"));
                                        }
                                    });
                            });
                        }

                    }

                    if (div.classList.contains('completed') || div.classList.contains('archived')) {
                        taskListCompleted.appendChild(div);
                        taskListCompleted.style.display = "block";
                    } else {
                        taskList.appendChild(div);
                    }
                });
            });

        fetch("/api/fund.php")
            .then(res => res.json())
            .then(bank => {
                const text = `$${bank.available} available`;
                const reserved = bank.reserved > 0 ? ` ($${bank.reserved} reserved)` : '';
                bankDisplay.textContent = text + reserved;
            });
    }


    function getFileIcon(file) {
        const ext = file.split('.').pop().toLowerCase();
        const known = ['pdf', 'jpg', 'png', 'zip', 'docx', 'txt', 'csv', 'mp4', 'wav', 'ai', 'psd', 'blend', 'obj'];
        return `/assets/icons/filetypes/${known.includes(ext) ? ext : 'default'}.png`;
    }

    function updateCountdowns() {
        document.querySelectorAll(".countdown").forEach(el => {
            const end = new Date(el.dataset.end);
            const now = new Date();
            const diff = end - now;
            const container = el.closest(".task");
            const taskId = container ?.dataset.id;
            const estimatedMinutes = parseFloat(el.dataset.estimated || "0");
            const estimatedMs = parseFloat(el.dataset.estimatedMs);
            if (isNaN(diff) || diff <= 0) {
                el.textContent = "Expired";

                if (!el.dataset.reset && taskId && passcode) {
                    el.dataset.reset = "true";
                    fetch("/api/reset_expired.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `task_id=${taskId}&passcode=${encodeURIComponent(passcode)}`
                    })
                        .then(res => res.json())
                        .then(result => {
                            if (result.success) location.reload();
                        });
                }
            } else {
                const mins = diff / 60000;

                const total = estimatedMs / 1000;
                const elapsed = total - diff / 1000;
                //console.log(elapsed);
                const progressRatio = Math.max(0, Math.min(1, elapsed / total));
                const barSegments = 20;
                const filledSegments = Math.round(barSegments * progressRatio);
                const stripes = Array.from({ length: filledSegments }, () => '<div></div>').join("");

                el.innerHTML = `
                <div class="progress-bar">
                    <div class="progress-stripe">${stripes}</div>
                </div>in progress <br> [${formatTime(mins, true)}]
            `;
            }
        });
    }


    setInterval(updateCountdowns, 1000);
    updateCountdowns();


    setInterval(() => {
        fetch("/api/reset_expired.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "global_reset=true"
        })
            .then(res => res.json())
            .then(result => {
                if (result.success && result.reset > 0) {
                    console.log(`Reset ${result.reset} expired tasks`);
                    location.reload(); // or optionally just refresh task list
                }
            })
            .catch(err => console.error("Global reset failed", err));
    }, 10000);

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatTime(minutes, includeSeconds = false) {
        const totalMs = minutes * 60 * 1000;
        const mins = Math.floor(totalMs / 60000);
        const secs = Math.floor((totalMs % 60000) / 1000);
        const hrs = Math.floor(mins / 60);
        const remMins = mins % 60;

        if (includeSeconds) {
            if (hrs > 0) return `${hrs}h ${remMins}m ${secs}s`;
            if (remMins > 0) return `${remMins}m ${secs}s`;
            return `${secs}s`;
        } else {
            if (hrs > 0 && remMins > 0) return `${hrs}h ${remMins}m`;
            if (hrs > 0) return `${hrs}h`;
            return `${remMins}m`;
        }
    }

    // Mobile-friendly enhancements
    function addMobileEnhancements() {
        // Add tap highlighting removal for better mobile UX
        if ('ontouchstart' in window) {
            document.body.style.webkitTapHighlightColor = 'transparent';
            
            // Improve mobile form focus behavior
            const inputs = document.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    // Small delay to ensure viewport adjustment
                    setTimeout(() => {
                        if (input.scrollIntoViewIfNeeded) {
                            input.scrollIntoViewIfNeeded();
                        } else {
                            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 300);
                });
            });
        }
    }

    // Initialize mobile enhancements
    addMobileEnhancements();

    // Dark mode functionality
    function initDarkMode() {
        const toggle = document.getElementById('darkModeToggle');
        if (!toggle) return;

        // Check for saved theme preference or default to system preference
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        let isDark = false;
        if (savedTheme) {
            isDark = savedTheme === 'dark';
        } else {
            isDark = systemPrefersDark;
        }

        // Apply initial theme
        applyTheme(isDark);
        toggle.checked = isDark;

        // Toggle event listener
        toggle.addEventListener('change', () => {
            const newTheme = toggle.checked;
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme ? 'dark' : 'light');
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only apply system theme if user hasn't manually set a preference
            if (!localStorage.getItem('theme')) {
                applyTheme(e.matches);
                toggle.checked = e.matches;
            }
        });
    }

    function applyTheme(isDark) {
        const root = document.documentElement;
        if (isDark) {
            root.classList.add('dark-theme');
            root.classList.remove('light-theme');
        } else {
            root.classList.add('light-theme');
            root.classList.remove('dark-theme');
        }
    }

    // Initialize dark mode
    initDarkMode();
});
