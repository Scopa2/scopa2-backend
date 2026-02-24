# Architecture Report: Globally Distributed Card Game

## 1. System Overview

The system is a globally distributed, multiplayer turn-based card game. The architecture decouples the stateless game logic from persistent storage to ensure scalability and fault tolerance. By deploying via Kubernetes, the system leverages container orchestration for high availability. The design strictly prioritizes **Consistency and Partition Tolerance (CP)** over Availability, ensuring that game states remain synchronized and fair, even in the event of network partitions.

## 2. Core Components

### 2.1. Client-Side (Godot)

The client acts as the user interface and terminal. It communicates with the backend via REST/WebSockets. It is responsible for gathering initial latency metrics (pings) to various geographic regions and polling the global routing services when network interruptions occur.

### 2.2. Global Control Plane

* **Health Checker & Matchmaker**: A custom, globally distributed service. It receives matchmaking requests (along with client ping data to all regions), groups players by ELO, and assigns the match to the optimal geographic replica based on the collected pings.
* **Global Redis (Master-Slave)**: Acts as the shared state layer for the Health Checkers. It stores the active `game_id -> replica_address -> player_ips` routing tables. A master-slave configuration ensures high availability of this routing data.
* **YugabyteDB (Persistent Storage)**: A distributed SQL database based on the Raft consensus protocol. It acts as the ultimate source of truth, storing user profiles, ELO ratings, match histories, and periodic game state checkpoints.

### 2.3. Regional Data Planes (Kubernetes Clusters)

* **Game Engine (Laravel)**: A stateless backend service handling the game rules and validating player moves.
* **Regional Redis (Soft State)**: A local caching layer used as a **Shared Dataspace**. It stores the highly volatile, second-by-second state of active matches within that geographic region.

## 3. System Workflows and Distributed Mechanisms

### 3.1. Matchmaking and Concurrency Control

When players search for a game, they ping all available regional clusters and submit these metrics to the global Health Checker.
To prevent race conditions where multiple Health Checker instances might attempt to pair the same players simultaneously, the system employs **Concurrency Control** via the Global Redis. By utilizing atomic operations (e.g., Redis Lua Scripts or the Redlock algorithm), the matchmaking queues are safely locked during player extraction, ensuring a strictly isolated and deterministic matchmaking process.

### 3.2. Active Gameplay (Soft State & Output Commits)

During an active match, the system prioritizes low latency without sacrificing consistency.

1. Player moves are sent to the regional Laravel Game Engine.
2. The engine validates the move, retrieves the current state from the Regional Redis, applies the move, and saves it back to Redis.
3. Because YugabyteDB cross-region consensus is slow, the game operates entirely on the "Soft State" within Redis during active turns.
4. The system performs an **Output Commit** to YugabyteDB only at specific, safe intervals (e.g., periodic asynchronous checkpoints) and at the end of the match to record the final ELO and game result.

### 3.3. Fault Tolerance and State Machine Replication

If a geographic replica experiences a fail-stop crash, the system executes a **Server-Driven Recovery** process to restore the game state:

1. **Detection**: The global Health Checker detects the regional cluster failure via missing heartbeats.
2. **Reassignment**: The Health Checker consults the Global Redis routing table to find affected `game_id`s. It uses the *original ping data* collected during matchmaking to elect the next best available regional cluster.
3. **Rollback Recovery**: The newly assigned regional cluster performs a state rebuild. It pulls the latest reliable checkpoint from YugabyteDB and loads it into its local Regional Redis.
4. **Client Reconnection**: The clients, having noticed the dropped connection, poll the Health Checker. The Health Checker provides the address of the newly assigned replica, and the players seamlessly resume their match from the last synchronized checkpoint.

## 4. Educational Justification

This architecture successfully navigates the CAP theorem by understanding the specific needs of a turn-based game (Consistency). It intentionally avoids off-the-shelf "black box" solutions like Global Server Load Balancing (GSLB) in favor of implementing custom consensus, concurrency control, and logging/checkpointing recovery managers. This directly demonstrates mastery of the core tenets of Distributed Software Systems.
