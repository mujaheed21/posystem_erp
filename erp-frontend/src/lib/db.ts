import Dexie, { type Table } from 'dexie';

/**
 * 4. Invariant Enforcement & Logistics
 * Offline Storage: Dexie.js (IndexedDB wrapper)
 */
export interface OfflineSale {
    id?: number;
    payload: any;
    synced: number; // 0 for pending, 1 for synced
    createdAt: number;
}

export class ERPDatabase extends Dexie {
    // 'type Table' ensures compatibility with verbatimModuleSyntax
    offlineSales!: Table<OfflineSale>;

    constructor() {
        super('ERP_Offline_DB');
        
        // Define schema for offline-capable workflows
        this.version(1).stores({
            offlineSales: '++id, synced, createdAt' 
        });
    }
}

export const db = new ERPDatabase();