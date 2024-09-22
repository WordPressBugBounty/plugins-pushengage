(function () {
  try {
    if (
      typeof _peq === "undefined" ||
      typeof pushengageCategorySegment === "undefined" ||
      typeof pushengageCategorySegment.addSegment !== "object"
    ) {
      return;
    }

    // find the new segments
    var newSegments = [];
    for (var segmentId in pushengageCategorySegment.addSegment) {
      var segmentName = pushengageCategorySegment.addSegment[segmentId];
      newSegments.push(segmentName);
    }

    // update the user segments
    if (newSegments.length) {
      window._peq.push(["add-to-segment", newSegments]);
    }
  } catch (e) {
    console.error(e);
  }
})();
