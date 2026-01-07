/**
 * script.js
 * NAIMOSPHERE Application Logic - Pollen Logic Added
 */

const App = {
  data: { currentCity: null, locations: [] },
  instances: { chart: null, map: null, markers: [] },

  init() {
    this.initMap();
    this.initDropdownUI();
    this.fetchMapData();
  },

  async fetchMapData() {
    const listEl = document.getElementById("city-list");
    listEl.innerHTML =
      '<li class="px-6 py-3 text-gray-400 text-sm italic">Lade Standorte...</li>';

    try {
      const response = await fetch("unload.php?mode=overview");
      if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
      const data = await response.json();

      if (data.success && data.locations.length > 0) {
        this.data.locations = data.locations;
        this.populateDropdown(data.locations);
        this.renderMapMarkers(data.locations);
      } else {
        listEl.innerHTML =
          '<li class="px-6 py-3 text-red-400 text-sm">Keine Daten. Bitte load.php starten.</li>';
      }
    } catch (error) {
      console.error(error);
      listEl.innerHTML =
        '<li class="px-6 py-3 text-red-400 text-sm">API Fehler.</li>';
    }
  },

  async fetchCityDetail(city) {
    try {
      const response = await fetch(
        `unload.php?city=${encodeURIComponent(city)}`
      );
      const data = await response.json();
      if (data.success) this.updateDashboard(data);
    } catch (error) {
      console.error(error);
    }
  },

  populateDropdown(locations) {
    const listEl = document.getElementById("city-list");
    listEl.innerHTML = "";
    locations
      .sort((a, b) => a.city.localeCompare(b.city))
      .forEach((loc) => {
        const li = document.createElement("li");
        li.className =
          "px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 cursor-pointer transition-colors text-sm font-medium border-b border-gray-50 last:border-0 flex justify-between items-center";
        let dotColor =
          loc.status === "good"
            ? "bg-emerald-500"
            : loc.status === "moderate"
            ? "bg-orange-500"
            : loc.status === "poor"
            ? "bg-rose-500"
            : "bg-gray-300";
        li.innerHTML = `<span>${loc.city}</span><span class="w-2 h-2 rounded-full ${dotColor}"></span>`;
        li.addEventListener("click", () => {
          this.handleCitySelect(loc.city);
          this.toggleDropdown(false);
        });
        listEl.appendChild(li);
      });
  },

  handleCitySelect(city) {
    document.getElementById("selected-city-text").innerText = city;
    document.getElementById("selected-city-text").classList.add("text-white");

    const emptyState = document.getElementById("empty-state");
    const dashboard = document.getElementById("dashboard-content");

    if (!emptyState.classList.contains("hidden")) {
      emptyState.classList.add("hidden");
      dashboard.classList.remove("hidden");
      dashboard.classList.add("animate-fade-in");
      setTimeout(() => {
        this.instances.map.invalidateSize();
        if (!this.instances.chart) this.initChart(city, [], [], []);
      }, 100);
    }
    this.fetchCityDetail(city);
  },

  updateDashboard(data) {
    const current = data.current;
    const history = data.history;

    // --- UI Updates ---
    const levelTextEl = document.getElementById("current-level");
    const levelBarEl = document.getElementById("status-indicator-bar");
    const aqiBarEl = document.getElementById("aqi-bar");
    const aqiValEl = document.getElementById("current-aqi");

    levelTextEl.innerText = current.level || "Unbekannt";
    aqiValEl.innerText = current.aqi ?? "--";
    const percentage = Math.min(((current.aqi || 0) / 150) * 100, 100);
    aqiBarEl.style.width = `${percentage}%`;

    // Colors
    const resetColors = (el) => {
      el.className = el.className
        .replace(/\b(text|bg|shadow)-[a-z]+-\d+(?:\[[^\]]+\])?/g, "")
        .trim();
    };

    levelTextEl.classList.remove(
      "text-emerald-500",
      "text-amber-500",
      "text-rose-500",
      "text-gray-400"
    );
    levelBarEl.classList.remove(
      "bg-emerald-500",
      "bg-amber-500",
      "bg-rose-500",
      "bg-gray-200"
    );
    aqiBarEl.classList.remove(
      "bg-emerald-500",
      "bg-amber-500",
      "bg-rose-500",
      "bg-gray-400",
      "shadow-[0_0_10px_rgba(16,185,129,0.4)]",
      "shadow-[0_0_10px_rgba(245,158,11,0.4)]",
      "shadow-[0_0_10px_rgba(244,63,94,0.4)]"
    );

    let colorText, colorBg, shadowClass;
    if (current.aqi === null) {
      colorText = "text-gray-400";
      colorBg = "bg-gray-300";
      shadowClass = "";
    } else if (current.aqi <= 50) {
      colorText = "text-emerald-500";
      colorBg = "bg-emerald-500";
      shadowClass = "shadow-[0_0_10px_rgba(16,185,129,0.4)]";
    } else if (current.aqi <= 100) {
      colorText = "text-amber-500";
      colorBg = "bg-amber-500";
      shadowClass = "shadow-[0_0_10px_rgba(245,158,11,0.4)]";
    } else {
      colorText = "text-rose-500";
      colorBg = "bg-rose-500";
      shadowClass = "shadow-[0_0_10px_rgba(244,63,94,0.4)]";
    }

    levelTextEl.classList.add(colorText);
    levelBarEl.classList.add(colorBg);
    aqiBarEl.classList.add(colorBg);
    if (shadowClass) aqiBarEl.classList.add(shadowClass);

    // RECOMMENDATIONS (POLLEN, SENSITIVE, ACTIVITY)
    this.updateRecommendations(current.aqi, current.pollen);

    // Chart
    document.getElementById("chart-city-label").innerText = data.city;
    if (this.instances.chart) {
      this.instances.chart.data.labels = history.labels;
      this.instances.chart.data.datasets[0].label = data.city;
      this.instances.chart.data.datasets[0].data = history.city_values;
      this.instances.chart.data.datasets[1].data = history.swiss_values;
      this.instances.chart.update();
    } else {
      this.initChart(
        data.city,
        history.labels,
        history.city_values,
        history.swiss_values
      );
    }
  },

  updateRecommendations(aqi, pollen) {
    // --- 1. POLLEN (Max von Birke/Gras) ---
    const pollenEl = document.getElementById("pollen-value");
    let polTitle, polDesc, polClass;

    if (!pollen || pollen < 10) {
      polTitle = "Niedrig";
      polDesc = "Keine Belastung.";
      polClass = "text-emerald-600";
    } else if (pollen <= 50) {
      polTitle = "Mittel";
      polDesc = "Allergiker aufgepasst.";
      polClass = "text-amber-500";
    } else {
      polTitle = "Hoch";
      polDesc = "Fenster schließen.";
      polClass = "text-rose-600";
    }

    pollenEl.parentElement.innerHTML = `
            <span id="pollen-value" class="block ${polClass} font-bold mb-2 text-xl">${polTitle}</span>
            <span class="text-gray-500">${polDesc}</span>
        `;

    // --- 2. SENSIBLE GRUPPEN ---
    const sensibleEl = document
      .querySelectorAll(".grid > div")[1]
      .querySelector(".text-gray-600");
    let sensTitle, sensDesc, sensClass;
    if (aqi === null) {
      sensTitle = "--";
      sensDesc = "Keine Daten";
      sensClass = "text-gray-400";
    } else if (aqi < 40) {
      sensTitle = "Unbedenklich";
      sensDesc = "Geniessen Sie die Luft.";
      sensClass = "text-emerald-600";
    } else if (aqi < 70) {
      sensTitle = "Vorsicht";
      sensDesc = "Bei Symptomen weniger draußen sein.";
      sensClass = "text-amber-500";
    } else {
      sensTitle = "Gefahr";
      sensDesc = "Medikamente bereithalten.";
      sensClass = "text-rose-600";
    }
    sensibleEl.innerHTML = `<span class="block ${sensClass} font-bold mb-2 text-xl">${sensTitle}</span><span class="text-gray-500">${sensDesc}</span>`;

    // --- 3. AKTIVITÄTEN ---
    const actBlock = document
      .querySelectorAll(".grid > div")[2]
      .querySelector(".text-gray-600");
    let actTitle, actDesc, actClass;
    if (aqi === null) {
      actTitle = "--";
      actDesc = "Keine Daten";
      actClass = "text-gray-400";
    } else if (aqi < 50) {
      actTitle = "Perfekt";
      actDesc = "Ideal für intensiven Sport.";
      actClass = "text-emerald-600";
    } else if (aqi < 100) {
      actTitle = "Mäßig";
      actDesc = "Leichter Sport OK.";
      actClass = "text-amber-500";
    } else {
      actTitle = "Vermeiden";
      actDesc = "Training nach drinnen verlegen.";
      actClass = "text-rose-600";
    }
    actBlock.innerHTML = `<span class="block ${actClass} font-bold mb-2 text-xl">${actTitle}</span><span class="text-gray-500">${actDesc}</span>`;
  },

  initMap() {
    if (!document.getElementById("map")) return;
    this.instances.map = L.map("map", {
      center: [46.8182, 8.2275],
      zoom: 7.5,
      zoomSnap: 0.5,
      zoomControl: false,
      scrollWheelZoom: false,
    });
    L.tileLayer(
      "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",
      { attribution: "&copy; CARTO", className: "map-tiles-grayscale" }
    ).addTo(this.instances.map);
    this.instances.map.on("dragend", () => {
      setTimeout(() => {
        this.instances.map.flyTo([46.8182, 8.2275], 7.5, {
          animate: true,
          duration: 1.2,
        });
      }, 100);
    });
    const legend = L.control({ position: "topleft" });
    legend.onAdd = () => {
      const div = L.DomUtil.create("div", "legend-container");
      div.innerHTML = `<h4 class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-4 block">Legende</h4><div class="flex flex-col space-y-3"><div class="flex items-center"><span class="relative flex h-3 w-3 mr-3"><span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span></span><span class="text-sm font-medium text-gray-700">Gut</span></div><div class="flex items-center"><span class="relative flex h-3 w-3 mr-3"><span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span></span><span class="text-sm font-medium text-gray-700">Mittel</span></div><div class="flex items-center"><span class="relative flex h-3 w-3 mr-3"><span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span></span><span class="text-sm font-medium text-gray-700">Schlecht</span></div></div>`;
      return div;
    };
    legend.addTo(this.instances.map);
  },

  renderMapMarkers(locations) {
    if (!this.instances.map) return;
    this.instances.markers.forEach((m) => this.instances.map.removeLayer(m));
    this.instances.markers = [];
    locations.forEach((loc) => {
      let color =
        loc.status === "good"
          ? "bg-emerald-500"
          : loc.status === "moderate"
          ? "bg-orange-500"
          : loc.status === "poor"
          ? "bg-rose-500"
          : "bg-gray-400";
      let bg =
        loc.status === "good"
          ? "bg-emerald-400"
          : loc.status === "moderate"
          ? "bg-orange-400"
          : loc.status === "poor"
          ? "bg-rose-400"
          : "bg-gray-300";
      const icon = L.divIcon({
        className: "custom-div-icon",
        html: `<div class="relative flex items-center justify-center w-20 h-20 pointer-events-none"><div class="absolute inset-0 flex items-center justify-center" style="animation: emitter-rotate 20s linear infinite;"><span class="absolute w-10 h-10 rounded-full ${bg} blur-[4px] opacity-0" style="animation: wind-puff 8s ease-out infinite;"></span></div><span class="relative inline-flex w-3 h-3 rounded-full ${color} border-2 border-white shadow-sm z-10"></span></div>`,
        iconSize: [80, 80],
        iconAnchor: [40, 40],
      });
      const m = L.marker([loc.lat, loc.lng], { icon: icon })
        .bindTooltip(`${loc.city} (AQI: ${loc.aqi ?? "?"})`, {
          direction: "top",
          offset: [0, -16],
          className: "custom-tooltip",
        })
        .addTo(this.instances.map);
      m.on("click", () => {
        this.handleCitySelect(loc.city);
        this.toggleDropdown(false);
      });
      this.instances.markers.push(m);
    });
  },

  initChart(city, labels, cityData, swissData) {
    const ctxEl = document.getElementById("aqiChart");
    if (!ctxEl) return;
    const ctx = ctxEl.getContext("2d");
    const g1 = ctx.createLinearGradient(0, 0, 0, 400);
    g1.addColorStop(0, "rgba(217, 70, 239, 0.4)");
    g1.addColorStop(1, "rgba(217, 70, 239, 0)");
    const g2 = ctx.createLinearGradient(0, 0, 0, 400);
    g2.addColorStop(0, "rgba(6, 182, 212, 0.4)");
    g2.addColorStop(1, "rgba(6, 182, 212, 0)");

    this.instances.chart = new Chart(ctx, {
      type: "line",
      data: {
        labels: labels.length
          ? labels
          : [
              "1",
              "2",
              "3",
              "4",
              "5",
              "6",
              "7",
              "8",
              "9",
              "10",
              "11",
              "12",
              "13",
              "14",
            ],
        datasets: [
          {
            label: city,
            data: cityData,
            borderColor: "#d946ef",
            backgroundColor: g1,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: "#fff",
            pointBorderColor: "#d946ef",
            pointRadius: 4,
            pointHoverRadius: 6,
          },
          {
            label: "Schweiz Ø",
            data: swissData,
            borderColor: "#06b6d4",
            backgroundColor: g2,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: "#fff",
            pointBorderColor: "#06b6d4",
            pointRadius: 4,
            pointHoverRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: false, external: this.chartTooltipHandler },
        },
        scales: {
          x: { display: false, grid: { display: false } },
          y: {
            min: 0,
            max: 150,
            grid: { color: "#F3F4F6", borderDash: [5, 5] },
            ticks: { stepSize: 25 },
            border: { display: false },
          },
        },
        interaction: { mode: "index", intersect: false },
      },
    });
  },

  chartTooltipHandler(context) {
    const { chart, tooltip } = context;
    const el = document.getElementById("chartjs-tooltip");
    if (tooltip.opacity === 0) {
      el.style.opacity = 0;
      return;
    }
    if (tooltip.body) {
      const title = tooltip.title[0] || "";
      const lines = tooltip.body.map((b) => b.lines);
      let html = `<div class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2 text-left">${title}</div>`;
      lines.forEach((line, i) => {
        const pt = tooltip.dataPoints[i];
        const color = i === 0 ? "#d946ef" : "#06b6d4";
        html += `<div class="flex items-center justify-between gap-4 mb-1"><span class="text-sm font-medium text-gray-600 whitespace-nowrap">${pt.dataset.label}</span><span class="text-xl font-bold whitespace-nowrap" style="color: ${color}">${pt.formattedValue} <span class="text-[10px] text-gray-400 font-normal">AQI</span></span></div>`;
      });
      el.innerHTML = html;
    }
    const { offsetLeft: x, offsetTop: y } = chart.canvas;
    el.style.opacity = 1;
    el.style.top = y + tooltip.caretY + "px";
    el.style.left =
      x +
      tooltip.caretX +
      (tooltip.caretX > chart.width / 2 ? -el.offsetWidth - 20 : 20) +
      "px";
    el.style.transform = "translateY(-50%)";
  },

  initDropdownUI() {
    const els = {
      trigger: document.getElementById("dropdown-trigger"),
      options: document.getElementById("dropdown-options"),
      arrow: document.getElementById("dropdown-arrow"),
    };
    if (!els.trigger || !els.options) return;
    els.trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      this.toggleDropdown(els.options.classList.contains("hidden"));
    });
    document.addEventListener("click", (e) => {
      if (
        document.getElementById("custom-dropdown-container") &&
        !document.getElementById("custom-dropdown-container").contains(e.target)
      )
        this.toggleDropdown(false);
    });
  },

  toggleDropdown(show) {
    const options = document.getElementById("dropdown-options");
    const arrow = document.getElementById("dropdown-arrow");
    if (show) {
      options.classList.remove("hidden");
      if (arrow) arrow.style.transform = "rotate(180deg)";
    } else {
      options.classList.add("hidden");
      if (arrow) arrow.style.transform = "rotate(0deg)";
    }
  },
};

document.addEventListener("DOMContentLoaded", () => App.init());
