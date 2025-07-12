<!DOCTYPE html>
<html>
<head>
  <title>Task Tracker</title>
  <script src="assets/script.js?v=<?= time() ?>" defer></script>
  <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
</head>
<body>
<div id="top-bar">
  <div class="top-bar-left">
    <div class="top-bar-icon"><img src="assets/windows-95-loading.gif"></div>  
    <div title="Web-based Task Tracker 1.0">
      <strong>WBT 1.0</strong>
      <span id="taskStats" style="font-weight: normal; font-size: 0.9em;"></span>
    </div>  
    <span id="bankDisplay">Loading funds...</span>
  </div>

  <div class="top-bar-right">
    <div id="authControls">
      <input type="text" id="authField" placeholder="Enter passcode..." />
      <button id="authBtn">Authorize</button>
      <span id="authStatus"></span>
    </div>
    <button id="historyBtn" style="display:none;">View History</button>
  </div>
</div>

  <div id="taskList">Loading tasks...</div>
  <div id="taskListCompleted"></div>

</body>
</html>
