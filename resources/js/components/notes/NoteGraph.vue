<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';

const emit = defineEmits(['select']);

const canvas = ref(null);
const loading = ref(true);
let nodes = [];
let edges = [];
let animationFrame = null;
let dragNode = null;
let offsetX = 0;
let offsetY = 0;

async function loadGraph() {
    loading.value = true;
    try {
        const response = await axios.get(route('notes.graph'));
        const data = response.data;

        // Initialize node positions randomly
        const width = canvas.value?.width || 600;
        const height = canvas.value?.height || 400;
        nodes = data.nodes.map((node) => ({
            ...node,
            x: Math.random() * (width - 80) + 40,
            y: Math.random() * (height - 80) + 40,
            vx: 0,
            vy: 0,
        }));
        edges = data.edges;

        simulate();
    } finally {
        loading.value = false;
    }
}

function simulate() {
    const iterations = 120;
    let frame = 0;

    function step() {
        if (frame >= iterations) {
            draw();
            return;
        }
        frame++;
        applyForces();
        draw();
        animationFrame = requestAnimationFrame(step);
    }

    step();
}

function applyForces() {
    const width = canvas.value?.width || 600;
    const height = canvas.value?.height || 400;
    const nodeMap = Object.fromEntries(nodes.map((node) => [node.id, node]));

    // Repulsion between all nodes
    for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
            const nodeA = nodes[i];
            const nodeB = nodes[j];
            let dx = nodeA.x - nodeB.x;
            let dy = nodeA.y - nodeB.y;
            const distance = Math.sqrt(dx * dx + dy * dy) || 1;
            const force = 800 / (distance * distance);
            dx = (dx / distance) * force;
            dy = (dy / distance) * force;
            nodeA.vx += dx;
            nodeA.vy += dy;
            nodeB.vx -= dx;
            nodeB.vy -= dy;
        }
    }

    // Attraction along edges
    edges.forEach((edge) => {
        const source = nodeMap[edge.source];
        const target = nodeMap[edge.target];
        if (!source || !target) return;
        const dx = target.x - source.x;
        const dy = target.y - source.y;
        const distance = Math.sqrt(dx * dx + dy * dy) || 1;
        const force = (distance - 100) * 0.01;
        const fx = (dx / distance) * force;
        const fy = (dy / distance) * force;
        source.vx += fx;
        source.vy += fy;
        target.vx -= fx;
        target.vy -= fy;
    });

    // Center gravity
    nodes.forEach((node) => {
        node.vx += (width / 2 - node.x) * 0.001;
        node.vy += (height / 2 - node.y) * 0.001;
    });

    // Apply velocity with damping
    nodes.forEach((node) => {
        if (node === dragNode) return;
        node.vx *= 0.85;
        node.vy *= 0.85;
        node.x += node.vx;
        node.y += node.vy;
        node.x = Math.max(20, Math.min(width - 20, node.x));
        node.y = Math.max(20, Math.min(height - 20, node.y));
    });
}

function draw() {
    const ctx = canvas.value?.getContext('2d');
    if (!ctx) return;
    const width = canvas.value.width;
    const height = canvas.value.height;
    const nodeMap = Object.fromEntries(nodes.map((node) => [node.id, node]));

    ctx.clearRect(0, 0, width, height);

    // Draw edges
    ctx.strokeStyle = 'rgba(129, 140, 248, 0.25)';
    ctx.lineWidth = 1;
    edges.forEach((edge) => {
        const source = nodeMap[edge.source];
        const target = nodeMap[edge.target];
        if (!source || !target) return;
        ctx.beginPath();
        ctx.moveTo(source.x, source.y);
        ctx.lineTo(target.x, target.y);
        ctx.stroke();
    });

    // Draw nodes
    nodes.forEach((node) => {
        // Determine if node has connections
        const hasEdges = edges.some((edge) => edge.source === node.id || edge.target === node.id);

        ctx.beginPath();
        ctx.arc(node.x, node.y, hasEdges ? 6 : 4, 0, Math.PI * 2);
        ctx.fillStyle = hasEdges ? '#818cf8' : 'rgba(129, 140, 248, 0.4)';
        ctx.fill();

        // Label
        ctx.font = '10px system-ui, sans-serif';
        ctx.fillStyle = 'rgba(var(--color-text-secondary), 1)';
        ctx.textAlign = 'center';
        ctx.fillText(truncate(node.title, 20), node.x, node.y + 16);
    });
}

function truncate(text, maxLength) {
    return text.length > maxLength ? text.slice(0, maxLength) + '…' : text;
}

function getMouseNode(event) {
    const rect = canvas.value.getBoundingClientRect();
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;
    return nodes.find((node) => {
        const dx = node.x - mouseX;
        const dy = node.y - mouseY;
        return dx * dx + dy * dy < 100; // radius 10
    });
}

function onMouseDown(event) {
    const node = getMouseNode(event);
    if (!node) return;
    dragNode = node;
    const rect = canvas.value.getBoundingClientRect();
    offsetX = event.clientX - rect.left - node.x;
    offsetY = event.clientY - rect.top - node.y;
}

function onMouseMove(event) {
    if (!dragNode) {
        const node = getMouseNode(event);
        canvas.value.style.cursor = node ? 'pointer' : 'default';
        return;
    }
    const rect = canvas.value.getBoundingClientRect();
    dragNode.x = event.clientX - rect.left - offsetX;
    dragNode.y = event.clientY - rect.top - offsetY;
    draw();
}

function onMouseUp() {
    dragNode = null;
}

function onClick(event) {
    const node = getMouseNode(event);
    if (node) {
        emit('select', node.id);
    }
}

onMounted(() => {
    if (canvas.value) {
        const parent = canvas.value.parentElement;
        canvas.value.width = parent.clientWidth;
        canvas.value.height = parent.clientHeight;
    }
    loadGraph();
});

onBeforeUnmount(() => {
    if (animationFrame) cancelAnimationFrame(animationFrame);
});
</script>

<template>
    <div class="relative w-full h-full overflow-hidden">
        <canvas
            ref="canvas"
            class="block w-full h-full"
            v-on:mousedown="onMouseDown"
            v-on:mousemove="onMouseMove"
            v-on:mouseup="onMouseUp"
            v-on:click="onClick"
        />
        <div
            v-if="loading"
            class="absolute inset-0 flex items-center justify-center bg-surface/80"
        >
            <span class="text-xs text-muted">Loading graph…</span>
        </div>
    </div>
</template>
