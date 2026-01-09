/**
 * script.js
 * NAIMOSPHERE Application Logic
 */

const App = {
  data: {
    currentCity: null,
    currentRange: "14d",
    locations: [],
  },
  instances: { chart: null, map: null, markers: [] },

  init() {
    this.initMap();
    this.initDropdownUI();
    this.initTimeFilters();
    this.fetchMapData();
  },

  async fetchMapData() {
    const listEl = document.getElementById("city-list");
    listEl.innerHTML =
      '<li class="px-6 py-3 text-gray-400 text-sm italic">Lade Standorte...</li>';

    try {
      const response = await fetch("unload.php?mode=overview&t=" + Date.now());
      if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
      const data = await response.json();

      if (data.success && data.locations.length > 0) {
        this.data.locations = data.locations;
        this.populateDropdown(data.locations);
        this.renderMapMarkers(data.locations);
      } else {
        listEl.innerHTML =
          '<li class="px-6 py-3 text-red-400 text-sm">Keine Daten verfügbar.</li>';
      }
    } catch (error) {
      console.error(error);
      listEl.innerHTML =
        '<li class="px-6 py-3 text-red-400 text-sm">Verbindungsfehler zur API.</li>';
    }
  },

  initTimeFilters() {
    const buttons = document.querySelectorAll(".time-filter");
    buttons.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const range = btn.dataset.range;
        this.changeRange(range);
      });
    });
  },

  changeRange(range) {
    if (this.data.currentRange === range) return;
    this.data.currentRange = range;

    document.querySelectorAll(".time-filter").forEach((btn) => {
      if (btn.dataset.range === range) {
        btn.classList.add("bg-white", "shadow-sm", "text-blue-600");
        btn.classList.remove("text-gray-500");
      } else {
        btn.classList.remove("bg-white", "shadow-sm", "text-blue-600");
        btn.classList.add("text-gray-500");
      }
    });

    if (this.data.currentCity) {
      this.fetchCityDetail(this.data.currentCity);
    }
  },

  async fetchCityDetail(city) {
    this.data.currentCity = city;
    try {
      const response = await fetch(
        `unload.php?city=${encodeURIComponent(city)}&range=${
          this.data.currentRange
        }&t=${Date.now()}`
      );
      const data = await response.json();
      if (data.success) {
        this.updateDashboard(data);
      }
    } catch (error) {
      console.error("Fehler beim Laden der Stadtdaten:", error);
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

        let dotColor = "bg-gray-300";
        if (loc.status === "good") dotColor = "bg-emerald-500";
        else if (loc.status === "moderate") dotColor = "bg-orange-500";
        else if (loc.status === "poor") dotColor = "bg-rose-500";

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
        if (this.instances.map) {
          this.instances.map.invalidateSize();
        }
      }, 100);
    }

    dashboard.scrollIntoView({ behavior: "smooth", block: "start" });
    this.fetchCityDetail(city);
  },

  updateDashboard(data) {
    const current = data.current;
    const history = data.history;

    const levelTextEl = document.getElementById("current-level");
    const levelBarEl = document.getElementById("status-indicator-bar");
    const aqiBarEl = document.getElementById("aqi-bar");
    const aqiValEl = document.getElementById("current-aqi");

    levelTextEl.innerText = current.level || "Unbekannt";
    aqiValEl.innerText = current.aqi ?? "--";

    const aqiNum = current.aqi || 0;
    const percentage = Math.min((aqiNum / 150) * 100, 100);
    aqiBarEl.style.width = `${percentage}%`;

    levelTextEl.className =
      "text-4xl md:text-5xl font-bold tracking-tight transition-colors duration-500";
    levelBarEl.className =
      "h-20 w-1.5 rounded-full hidden md:block opacity-80 transition-colors duration-500";
    aqiBarEl.className = "h-full rounded-full transition-all duration-1000";

    let colorText = "text-gray-400";
    let colorBg = "bg-gray-300";
    let shadowClass = "";

    if (current.aqi !== null) {
      if (current.aqi <= 50) {
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
    }

    levelTextEl.classList.add(colorText);
    levelBarEl.classList.add(colorBg);
    aqiBarEl.classList.add(colorBg);
    if (shadowClass) aqiBarEl.classList.add(shadowClass);

    this.updateRecommendations(current.aqi, current.pollen);
    document.getElementById("chart-city-label").innerText = data.city;

    const cleanValues = (arr) => {
      if (!Array.isArray(arr)) return [];
      return arr.map((v) => {
        if (v === null || v === undefined) return null;
        const num = parseFloat(v);
        return isNaN(num) ? null : num;
      });
    };

    this.initChart(
      data.city,
      history.labels,
      cleanValues(history.city_values),
      cleanValues(history.swiss_values)
    );
  },

  updateRecommendations(aqi, pollen) {
    const pollenEl = document.getElementById("pollen-value");
    let polTitle = "Niedrig",
      polDesc = "Keine Belastung.",
      polClass = "text-emerald-600";

    if (pollen && pollen >= 10) {
      if (pollen <= 50) {
        polTitle = "Mittel";
        polDesc = "Allergiker aufgepasst.";
        polClass = "text-amber-500";
      } else {
        polTitle = "Hoch";
        polDesc = "Fenster schließen.";
        polClass = "text-rose-600";
      }
    }

    pollenEl.parentElement.innerHTML = `
        <span id="pollen-value" class="block ${polClass} font-bold mb-2 text-xl">${polTitle}</span>
        <span class="text-gray-500">${polDesc}</span>
    `;

    const sensibleEl = document
      .querySelectorAll(".grid > div")[1]
      .querySelector(".text-gray-600");
    let sensTitle = "Unbedenklich",
      sensDesc = "Geniessen Sie die Luft.",
      sensClass = "text-emerald-600";

    if (aqi === null) {
      sensTitle = "--";
      sensDesc = "Keine Daten";
      sensClass = "text-gray-400";
    } else if (aqi >= 40 && aqi < 70) {
      sensTitle = "Vorsicht";
      sensDesc = "Bei Symptomen aufpassen.";
      sensClass = "text-amber-500";
    } else if (aqi >= 70) {
      sensTitle = "Gefahr";
      sensDesc = "Medikamente bereithalten.";
      sensClass = "text-rose-600";
    }
    sensibleEl.innerHTML = `<span class="block ${sensClass} font-bold mb-2 text-xl">${sensTitle}</span><span class="text-gray-500">${sensDesc}</span>`;

    const actBlock = document
      .querySelectorAll(".grid > div")[2]
      .querySelector(".text-gray-600");
    let actTitle = "Perfekt",
      actDesc = "Ideal für Sport.",
      actClass = "text-emerald-600";

    if (aqi === null) {
      actTitle = "--";
      actDesc = "Keine Daten";
      actClass = "text-gray-400";
    } else if (aqi >= 50 && aqi < 100) {
      actTitle = "Mäßig";
      actDesc = "Leichter Sport OK.";
      actClass = "text-amber-500";
    } else if (aqi >= 100) {
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
      div.innerHTML = `
        <h4 class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-4 block">Legende</h4>
        <div class="flex flex-col space-y-3">
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-emerald-500 mr-3"></span><span class="text-sm font-medium text-gray-700">Gut</span></div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-orange-500 mr-3"></span><span class="text-sm font-medium text-gray-700">Mittel</span></div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-rose-500 mr-3"></span><span class="text-sm font-medium text-gray-700">Schlecht</span></div>
        </div>`;
      return div;
    };
    legend.addTo(this.instances.map);
  },

  renderMapMarkers(locations) {
    if (!this.instances.map) return;
    this.instances.markers.forEach((m) => this.instances.map.removeLayer(m));
    this.instances.markers = [];

    locations.forEach((loc) => {
      let color = "bg-gray-400",
        bg = "bg-gray-300";

      if (loc.status === "good") {
        color = "bg-emerald-500";
        bg = "bg-emerald-400";
      } else if (loc.status === "moderate") {
        color = "bg-orange-500";
        bg = "bg-orange-400";
      } else if (loc.status === "poor") {
        color = "bg-rose-500";
        bg = "bg-rose-400";
      }

      const icon = L.divIcon({
        className: "custom-div-icon",
        html: `<div class="relative flex items-center justify-center w-20 h-20 pointer-events-none">
                 <div class="absolute inset-0 flex items-center justify-center" style="animation: emitter-rotate 20s linear infinite;">
                    <span class="absolute w-10 h-10 rounded-full ${bg} blur-[4px] opacity-0" style="animation: wind-puff 8s ease-out infinite;"></span>
                 </div>
                 <span class="relative inline-flex w-3 h-3 rounded-full ${color} border-2 border-white shadow-sm z-10"></span>
               </div>`,
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

    if (this.instances.chart) {
      this.instances.chart.destroy();
      this.instances.chart = null;
    }

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
        labels: labels.length ? labels : [],
        datasets: [
          {
            label: city,
            data: cityData,
            borderColor: "#d946ef",
            backgroundColor: g1,
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: "#fff",
            pointBorderColor: "#d946ef",
            pointRadius: 4,
            pointHoverRadius: 6,
            spanGaps: true,
          },
          {
            label: "Schweiz Ø",
            data: swissData,
            borderColor: "#06b6d4",
            backgroundColor: g2,
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: "#fff",
            pointBorderColor: "#06b6d4",
            pointRadius: 4,
            pointHoverRadius: 6,
            spanGaps: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        resizeDelay: 200,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: false, external: this.chartTooltipHandler },
        },
        scales: {
          x: { display: false, grid: { display: false } },
          y: {
            beginAtZero: true,
            min: 0,
            suggestedMax: 150,
            grid: { color: "#F3F4F6", borderDash: [5, 5] },
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

      let html = `<div class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2 text-left border-b border-gray-100 pb-1">${title}</div>`;

      lines.forEach((line, i) => {
        const pt = tooltip.dataPoints[i];
        const color = i === 0 ? "#d946ef" : "#06b6d4";

        if (pt.raw !== null && pt.raw !== undefined) {
          html += `<div class="flex items-center justify-between gap-4 mb-1">
                     <span class="text-sm font-medium text-gray-600 whitespace-nowrap flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" style="background:${color}"></span>${
            pt.dataset.label
          }
                     </span>
                     <span class="text-lg font-bold whitespace-nowrap text-gray-800">
                        ${Math.round(
                          pt.raw
                        )} <span class="text-[10px] text-gray-400 font-normal">AQI</span>
                     </span>
                   </div>`;
        } else {
          html += `<div class="flex items-center justify-between gap-4 mb-1">
                     <span class="text-sm font-medium text-gray-600 whitespace-nowrap flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-gray-300"></span>${pt.dataset.label}
                     </span>
                     <span class="text-sm italic text-gray-400">Keine Daten</span>
                   </div>`;
        }
      });
      el.innerHTML = html;
    }

    const { offsetLeft: x, offsetTop: y } = chart.canvas;
    let leftPos = x + tooltip.caretX + 20;
    if (tooltip.caretX > chart.width * 0.7) {
      leftPos = x + tooltip.caretX - el.offsetWidth - 20;
    }

    el.style.opacity = 1;
    el.style.top = y + tooltip.caretY + "px";
    el.style.left = leftPos + "px";
    el.style.transform = "translateY(-50%)";
  },

  initDropdownUI() {
    const trigger = document.getElementById("dropdown-trigger");
    const options = document.getElementById("dropdown-options");
    const container = document.getElementById("custom-dropdown-container");

    if (!trigger || !options) return;

    trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      this.toggleDropdown(options.classList.contains("hidden"));
    });

    document.addEventListener("click", (e) => {
      if (container && !container.contains(e.target)) {
        this.toggleDropdown(false);
      }
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
