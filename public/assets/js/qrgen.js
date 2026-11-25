var QrGen = (function () {
  var EXP = new Array(512), LOG = new Array(256);
  (function () {
    var x = 1;
    for (var i = 0; i < 255; i++) {
      EXP[i] = x; LOG[x] = i;
      x <<= 1;
      if (x & 0x100) x ^= 0x11d;
    }
    for (var j = 255; j < 512; j++) EXP[j] = EXP[j - 255];
  })();
  function gfMul(a, b) { return (a && b) ? EXP[LOG[a] + LOG[b]] : 0; }
  function rsGen(n) {
    var poly = [1];
    for (var i = 0; i < n; i++) {
      var a = EXP[i], next = [], j;
      for (j = 0; j <= poly.length; j++) next.push(0);
      for (j = 0; j <= poly.length; j++) {
        next[j] ^= (j < poly.length ? poly[j] : 0) ^ (j > 0 ? gfMul(poly[j - 1], a) : 0);
      }
      poly = next;
    }
    return poly;
  }
  function rsEncode(data, n) {
    var gen = rsGen(n);
    var msg = data.concat(new Array(n).fill(0));
    for (var i = 0; i < data.length; i++) {
      var coef = msg[i];
      if (coef !== 0) {
        for (var j = 0; j < gen.length; j++) msg[i + j] ^= gfMul(gen[j], coef);
      }
    }
    return msg.slice(data.length);
  }
  var EC_TABLE = { 1: [10, 1, 16], 2: [16, 1, 28], 3: [26, 1, 44], 4: [18, 2, 32], 5: [24, 2, 43], 6: [16, 4, 27] };
  var ALIGN = { 1: [], 2: [6, 18], 3: [6, 22], 4: [6, 26], 5: [6, 30], 6: [6, 34] };
  var REMAINDER_BITS = { 1: 0, 2: 7, 3: 7, 4: 7, 5: 7, 6: 7 };
  function totalDataCw(v) { var t = EC_TABLE[v]; return t[1] * t[2]; }
  function byteCapacity(v) { return totalDataCw(v) - 2; }
  function buildDataCodewords(bytes, v) {
    var total = totalDataCw(v);
    var bits = [];
    function push(val, len) { for (var i = len - 1; i >= 0; i--) bits.push((val >>> i) & 1); }
    push(0x4, 4);
    push(bytes.length, 8);
    for (var i = 0; i < bytes.length; i++) push(bytes[i], 8);
    var cap = total * 8;
    var term = Math.min(4, cap - bits.length);
    for (var t = 0; t < term; t++) bits.push(0);
    while (bits.length % 8 !== 0) bits.push(0);
    var out = [];
    for (var b = 0; b < bits.length; b += 8) {
      var byte = 0;
      for (var k = 0; k < 8; k++) byte = (byte << 1) | bits[b + k];
      out.push(byte);
    }
    var pads = [0xec, 0x11], p = 0;
    while (out.length < total) { out.push(pads[p % 2]); p++; }
    return out;
  }
  function interleave(bytes, v) {
    var t = EC_TABLE[v], ecLen = t[0], nBlocks = t[1], perBlock = t[2];
    var blocks = [], ecBlocks = [], i, j;
    for (i = 0; i < nBlocks; i++) {
      var block = bytes.slice(i * perBlock, (i + 1) * perBlock);
      blocks.push(block);
      ecBlocks.push(rsEncode(block, ecLen));
    }
    var out = [];
    for (j = 0; j < perBlock; j++) for (i = 0; i < nBlocks; i++) out.push(blocks[i][j]);
    for (j = 0; j < ecLen; j++) for (i = 0; i < nBlocks; i++) out.push(ecBlocks[i][j]);
    return out;
  }
  function formatBits(mask) {
    var data = mask;
    var rem = data;
    for (var i = 0; i < 10; i++) rem = (rem << 1) ^ ((rem >>> 9) * 0x537);
    return ((data << 10) | rem) ^ 0x5412;
  }
  function buildMatrix(codewords, v, mask) {
    var size = 17 + 4 * v;
    var mod = [], fn = [], r, c;
    for (r = 0; r < size; r++) {
      mod.push(new Array(size).fill(false));
      fn.push(new Array(size).fill(false));
    }
    function setF(x, y, val) { if (x >= 0 && x < size && y >= 0 && y < size) { mod[y][x] = val; fn[y][x] = true; } }
    for (var i = 0; i < size; i++) {
      setF(6, i, i % 2 === 0);
      setF(i, 6, i % 2 === 0);
    }
    function finder(cx, cy) {
      for (var dy = -4; dy <= 4; dy++) {
        for (var dx = -4; dx <= 4; dx++) {
          var dist = Math.max(Math.abs(dx), Math.abs(dy));
          setF(cx + dx, cy + dy, dist !== 2 && dist !== 4);
        }
      }
    }
    finder(3, 3); finder(size - 4, 3); finder(3, size - 4);
    var pos = ALIGN[v];
    for (var a = 0; a < pos.length; a++) {
      for (var b = 0; b < pos.length; b++) {
        var x = pos[a], y = pos[b];
        if ((x === 6 && y === 6) || (x === 6 && y === size - 7) || (x === size - 7 && y === 6)) continue;
        for (var dy = -2; dy <= 2; dy++) {
          for (var dx = -2; dx <= 2; dx++) {
            setF(x + dx, y + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
          }
        }
      }
    }
    function drawFormat(m) {
      var bits = formatBits(m), i;
      function bit(idx) { return ((bits >>> idx) & 1) !== 0; }
      for (i = 0; i <= 5; i++) setF(8, i, bit(i));
      setF(8, 7, bit(6));
      setF(8, 8, bit(7));
      setF(7, 8, bit(8));
      for (i = 9; i < 15; i++) setF(14 - i, 8, bit(i));
      for (i = 0; i < 8; i++) setF(size - 1 - i, 8, bit(i));
      for (i = 8; i < 15; i++) setF(8, size - 15 + i, bit(i));
      setF(8, size - 8, true);
    }
    drawFormat(0);
    var bits = [];
    for (var w = 0; w < codewords.length; w++) {
      for (var kb = 7; kb >= 0; kb--) bits.push((codewords[w] >>> kb) & 1);
    }
    for (var rb = 0; rb < REMAINDER_BITS[v]; rb++) bits.push(0);
    var idx = 0;
    for (var right = size - 1; right >= 1; right -= 2) {
      if (right === 6) right = 5;
      for (var vert = 0; vert < size; vert++) {
        for (var j = 0; j < 2; j++) {
          var x2 = right - j;
          var upward = ((right + 1) & 2) === 0;
          var y2 = upward ? size - 1 - vert : vert;
          if (!fn[y2][x2]) {
            mod[y2][x2] = idx < bits.length ? bits[idx] === 1 : false;
            idx++;
          }
        }
      }
    }
    function maskBit(m, rr, cc) {
      switch (m) {
        case 0: return (rr + cc) % 2 === 0;
        case 1: return rr % 2 === 0;
        case 2: return cc % 3 === 0;
        case 3: return (rr + cc) % 3 === 0;
        case 4: return (Math.floor(rr / 2) + Math.floor(cc / 3)) % 2 === 0;
        case 5: return (rr * cc) % 2 + (rr * cc) % 3 === 0;
        case 6: return ((rr * cc) % 2 + (rr * cc) % 3) % 2 === 0;
        case 7: return ((rr + cc) % 2 + (rr * cc) % 3) % 2 === 0;
      }
      return false;
    }
    for (r = 0; r < size; r++) {
      for (c = 0; c < size; c++) {
        if (!fn[r][c] && maskBit(mask, r, c)) mod[r][c] = !mod[r][c];
      }
    }
    drawFormat(mask);
    return mod;
  }
  function penalty(mod) {
    var size = mod.length, score = 0, r, c;
    function runScore(line) {
      var s = 0, run = 1;
      for (var i = 1; i <= line.length; i++) {
        if (i < line.length && line[i] === line[i - 1]) run++;
        else { if (run >= 5) s += 3 + (run - 5); run = 1; }
      }
      return s;
    }
    for (r = 0; r < size; r++) {
      score += runScore(mod[r]);
      var col = [];
      for (c = 0; c < size; c++) col.push(mod[c][r]);
      score += runScore(col);
    }
    for (r = 0; r < size - 1; r++) {
      for (c = 0; c < size - 1; c++) {
        if (mod[r][c] === mod[r][c + 1] && mod[r][c] === mod[r + 1][c] && mod[r][c] === mod[r + 1][c + 1]) score += 3;
      }
    }
    var pat1 = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0], pat2 = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
    function patScore(line) {
      var s = 0;
      for (var i = 0; i + 11 <= line.length; i++) {
        var m1 = true, m2 = true;
        for (var k = 0; k < 11; k++) {
          var v = line[i + k] ? 1 : 0;
          if (v !== pat1[k]) m1 = false;
          if (v !== pat2[k]) m2 = false;
        }
        if (m1 || m2) s += 40;
      }
      return s;
    }
    for (r = 0; r < size; r++) {
      score += patScore(mod[r]);
      var col2 = [];
      for (c = 0; c < size; c++) col2.push(mod[c][r]);
      score += patScore(col2);
    }
    var dark = 0;
    for (r = 0; r < size; r++) for (c = 0; c < size; c++) if (mod[r][c]) dark++;
    var total = size * size;
    var k2 = Math.ceil(Math.abs(dark * 20 - total * 10) / total) - 1;
    if (k2 > 0) score += k2 * 10;
    return score;
  }
  function encode(text) {
    var bytes = [];
    for (var i = 0; i < text.length; i++) {
      var code = text.charCodeAt(i);
      if (code > 0x7f) throw new Error('Non-ASCII input');
      bytes.push(code);
    }
    var v = 0;
    for (var cand = 1; cand <= 6; cand++) {
      if (bytes.length <= byteCapacity(cand)) { v = cand; break; }
    }
    if (!v) throw new Error('Input too long');
    var dataCw = buildDataCodewords(bytes, v);
    var finalCw = interleave(dataCw, v);
    var best = null, bestScore = Infinity;
    for (var m = 0; m < 8; m++) {
      var matrix = buildMatrix(finalCw, v, m);
      var s = penalty(matrix);
      if (s < bestScore) { bestScore = s; best = matrix; }
    }
    return { size: 17 + 4 * v, modules: best };
  }
  function svg(text, cellSize, margin) {
    var res = encode(text);
    var n = res.size, m = (margin == null ? 4 : margin);
    var dim = n + 2 * m;
    var parts = ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + dim + ' ' + dim + '" width="' + dim * cellSize + '" height="' + dim * cellSize + '" role="img" aria-label="Kode QR ' + text + '">'];
    parts.push('<rect width="' + dim + '" height="' + dim + '" fill="#ffffff"/>');
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (res.modules[r][c]) parts.push('<rect x="' + (c + m) + '" y="' + (r + m) + '" width="1" height="1" fill="#000000"/>');
      }
    }
    parts.push('</svg>');
    return parts.join('');
  }
  return { encode: encode, svg: svg };
})();
