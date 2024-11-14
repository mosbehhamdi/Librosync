import { ref } from 'vue';

class WebSocketService {
  private ws: WebSocket | null = null;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 3;
  private reconnectTimeout = 3000;
  private subscribers = new Map<string, ((data: any) => void)[]>();
  public isConnected = ref(false);
  public useFallback = ref(false);

  constructor() {
    this.connect();
  }

  private connect() {
    if (this.useFallback.value) return;

    try {
      const wsUrl = import.meta.env.VITE_WS_URL || 'ws://localhost:6001/ws';
      this.ws = new WebSocket(wsUrl);

      this.ws.onopen = () => {
        this.isConnected.value = true;
        this.reconnectAttempts = 0;
        this.resubscribeAll();
      };

      this.ws.onclose = () => {
        this.isConnected.value = false;
        this.useFallback.value = true;
        this.notifySubscribersOfFallback();
      };

      this.ws.onerror = () => {
        this.useFallback.value = true;
        this.notifySubscribersOfFallback();
      };
    } catch (error) {
      console.warn('WebSocket connection failed, using fallback mechanism');
      this.useFallback.value = true;
      this.notifySubscribersOfFallback();
    }
  }

  private handleReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      setTimeout(() => this.connect(), this.reconnectTimeout);
    } else {
      this.useFallback.value = true;
      this.notifySubscribersOfFallback();
    }
  }

  private resubscribeAll() {
    this.subscribers.forEach((callbacks, channel) => {
      this.sendSubscription(channel);
    });
  }

  private sendSubscription(channel: string) {
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ action: 'subscribe', channel }));
    }
  }

  private notifySubscribersOfFallback() {
    this.subscribers.forEach((callbacks, channel) => {
      callbacks.forEach(callback => {
        callback({ type: 'fallback_activated' });
      });
    });
  }

  public subscribe(channel: string, callback: (data: any) => void) {
    if (!this.subscribers.has(channel)) {
      this.subscribers.set(channel, []);
    }
    this.subscribers.get(channel)?.push(callback);

    if (this.useFallback.value) {
      callback({ type: 'fallback_activated' });
    } else if (this.isConnected.value) {
      this.sendSubscription(channel);
    }
  }

  public close() {
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
    this.subscribers.clear();
    this.isConnected.value = false;
  }

  public isFallbackMode() {
    return this.useFallback.value;
  }
}

export const wsService = new WebSocketService(); 