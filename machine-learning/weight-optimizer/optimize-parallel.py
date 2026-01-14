import json
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from tensorflow import keras
from tensorflow.keras import layers
import time
from datetime import datetime
import os
import multiprocessing as mp
from filelock import FileLock
import sys

# Event impact configuration (positive direction for USD)
EVENT_CONFIG = {
    'VPRWG': {'positive': 'higher'},  # Nonfarm Payrolls
    'ZBEYU': {'positive': 'higher'},  # Avg Hourly Earnings MoM
    'GKORG': {'positive': 'higher'},  # Avg Hourly Earnings YoY
    'JCDYM': {'positive': 'lower'},   # Unemployment Rate
    'LUXEM': {'positive': 'lower'},   # Initial Jobless Claims
    'EEWPQ': {'positive': 'lower'},   # Participation Rate
    'PIIRP': {'positive': 'lower'},   # Continuing Jobless Claims
    'YHIYY': {'positive': 'higher'},  # ADP Employment
    'MXSBY': {'positive': 'higher'},  # JOLTS
    'ASXLP': {'positive': 'higher'},  # CPI MoM
    'YFVMV': {'positive': 'higher'},  # Core CPI MoM
    'FCHGZ': {'positive': 'higher'},  # PCE Deflator MoM
    'RQUMP': {'positive': 'higher'},  # Core PCE
    'ISFDE': {'positive': 'higher'},  # CPI YoY
    'YPAXY': {'positive': 'higher'},  # Core CPI YoY
    'LUNIH': {'positive': 'higher'},  # PPI MoM
    'LIFLX': {'positive': 'higher'},  # Core PPI
    'HKJPS': {'positive': 'higher'},  # PCE YoY
    'WDLPA': {'positive': 'higher'},  # Core PCE YoY
    'ZORRY': {'positive': 'higher'},  # FOMC Press
    'HMUGW': {'positive': 'higher'},  # FOMC Statement
    'ZVAPL': {'positive': 'higher'},  # Fed Rate
    'ALMVK': {'positive': 'higher'},  # Powell Speaks
    'BRHWF': {'positive': 'higher'},  # Powell Testifies
    'BZLYI': {'positive': 'higher'},  # FOMC Projections
    'IEDWO': {'positive': 'higher'},  # FOMC Minutes
    'XWBVZ': {'positive': 'higher'},  # Fed Policy Report
    'ZJOIV': {'positive': 'higher'},  # Fed Balance Sheet
    'FLPVV': {'positive': 'higher'},  # Beige Book
}

# Global cache for events data
EVENTS_DATA = None

def load_events_data():
    """Load events data once per process"""
    global EVENTS_DATA
    if EVENTS_DATA is None:
        with open('events_data.json', 'r') as f:
            EVENTS_DATA = json.load(f)
    return EVENTS_DATA

def calculate_next_week_economy_state(events_data, weights):
    """Calculate economy state using provided weights"""
    if not events_data or 'vestor_data' not in events_data:
        return 0.0
    
    aggregate_weight = 0
    event_count = 0
    
    for category in events_data['vestor_data'].values():
        if 'events' not in category or not isinstance(category['events'], list):
            continue
        
        for event in category['events']:
            event_id = event.get('consistent_event_id')
            if event_id not in EVENT_CONFIG:
                continue
            
            forecast = event.get('forecast_value')
            previous = event.get('previous_value')
            
            if forecast is None or previous is None:
                continue
            
            try:
                forecast_val = float(forecast)
                previous_val = float(previous)
            except:
                continue
            
            # Skip unchanged
            if forecast_val == previous_val:
                continue
            
            # Get weight for this event
            weight = weights.get(event_id, 5.0)
            config = EVENT_CONFIG[event_id]
            
            # Calculate signed weight
            signed_weight = 0
            if config['positive'] == 'higher':
                if forecast_val > previous_val:
                    signed_weight = weight
                elif forecast_val < previous_val:
                    signed_weight = -weight
            elif config['positive'] == 'lower':
                if forecast_val < previous_val:
                    signed_weight = weight
                elif forecast_val > previous_val:
                    signed_weight = -weight
            
            aggregate_weight += signed_weight
            event_count += 1
    
    # Calculate percentage
    if event_count > 0:
        should_be_weight = event_count * 10
        state_percentage = (aggregate_weight / should_be_weight) * 100
        return round(state_percentage, 2)
    
    return 0.0

def generate_training_data(all_data, weights):
    """Generate training DataFrame with given weights"""
    rows = []
    for item in all_data:
        next_week_state = calculate_next_week_economy_state(item['events'], weights)
        rows.append({
            'date': item['date'],
            'next_week_state_of_the_economy': next_week_state,
            'direction': item['direction'],
            'magnitude': item['magnitude']
        })
    return pd.DataFrame(rows)

def train_and_evaluate(df):
    """Train LSTM and return accuracy"""
    X = df[['next_week_state_of_the_economy']].values
    y_direction = (df['direction'] == 'UP').astype(int).values
    
    # Split
    X_train, X_test, y_train, y_test = train_test_split(
        X, y_direction, test_size=0.2, random_state=42, shuffle=False
    )
    
    # Scale
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)
    
    # Reshape for LSTM
    X_train_lstm = X_train_scaled.reshape((X_train_scaled.shape[0], 1, 1))
    X_test_lstm = X_test_scaled.reshape((X_test_scaled.shape[0], 1, 1))
    
    # Build model
    model = keras.Sequential([
        layers.LSTM(64, activation='tanh', return_sequences=True, input_shape=(1, 1)),
        layers.Dropout(0.2),
        layers.LSTM(32, activation='tanh'),
        layers.Dropout(0.2),
        layers.Dense(16, activation='relu'),
        layers.Dense(1, activation='sigmoid')
    ])
    
    model.compile(
        optimizer=keras.optimizers.Adam(learning_rate=0.001),
        loss='binary_crossentropy',
        metrics=['accuracy']
    )
    
    # Train
    model.fit(
        X_train_lstm, y_train,
        epochs=30,
        batch_size=32,
        validation_split=0.2,
        verbose=0
    )
    
    # Evaluate
    y_pred_proba = model.predict(X_test_lstm, verbose=0).flatten()
    y_pred = (y_pred_proba > 0.5).astype(int)
    accuracy = np.mean(y_pred == y_test)
    
    return accuracy

def mutate_weights(weights, mutation_rate=0.3):
    """Mutate weights by ±1"""
    new_weights = weights.copy()
    for event_id in new_weights:
        if np.random.random() < mutation_rate:
            change = np.random.choice([-1, 1])
            new_weights[event_id] = max(0, min(10, new_weights[event_id] + change))
    return new_weights

def load_best_weights():
    """Thread-safe load of best weights"""
    best_weights_file = 'best_weights.json'
    lock = FileLock(f'{best_weights_file}.lock')
    
    with lock:
        if os.path.exists(best_weights_file):
            with open(best_weights_file, 'r') as f:
                return json.load(f)
    
    return None

def save_best_weights(weights, accuracy):
    """Thread-safe save of best weights"""
    best_weights_file = 'best_weights.json'
    lock = FileLock(f'{best_weights_file}.lock')
    
    with lock:
        # Check if we still have the best
        current_best = None
        if os.path.exists(best_weights_file):
            with open(best_weights_file, 'r') as f:
                current_best = json.load(f)
        
        # Only save if better
        if current_best is None or accuracy > current_best['accuracy']:
            with open(best_weights_file, 'w') as f:
                json.dump({
                    'accuracy': accuracy,
                    'weights': weights,
                    'timestamp': datetime.now().isoformat()
                }, f, indent=2)
            return True
    
    return False

def append_result(worker_id, iteration, accuracy, samples, weights):
    """Thread-safe append to results CSV"""
    results_file = 'optimization_results.csv'
    lock = FileLock(f'{results_file}.lock')
    
    with lock:
        result_row = {
            'worker': worker_id,
            'iteration': iteration,
            'timestamp': datetime.now().isoformat(),
            'accuracy': accuracy,
            'samples': samples
        }
        for event_id, weight in weights.items():
            result_row[f'w_{event_id}'] = weight
        
        result_df = pd.DataFrame([result_row])
        if not os.path.exists(results_file):
            result_df.to_csv(results_file, index=False)
        else:
            result_df.to_csv(results_file, mode='a', header=False, index=False)

def worker_process(worker_id, num_iterations):
    """Worker process that runs optimization iterations"""
    # Suppress TensorFlow warnings
    os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
    import warnings
    warnings.filterwarnings('ignore')
    
    # Load events data once
    all_data = load_events_data()
    
    # Load best weights
    saved = load_best_weights()
    if saved:
        current_weights = saved['weights']
        best_accuracy = saved['accuracy']
    else:
        current_weights = {event_id: 5.0 for event_id in EVENT_CONFIG.keys()}
        best_accuracy = 0.0
    
    print(f"[Worker {worker_id}] Started")
    
    for iteration in range(1, num_iterations + 1):
        start_time = time.time()
        
        # Generate new weights (with some randomness per worker)
        if iteration == 1 and worker_id == 0:
            test_weights = current_weights
        else:
            test_weights = mutate_weights(current_weights)
        
        # Generate training data
        df = generate_training_data(all_data, test_weights)
        
        # Train and evaluate
        accuracy = train_and_evaluate(df)
        
        elapsed = time.time() - start_time
        
        # Save results
        append_result(worker_id, iteration, accuracy, len(df), test_weights)
        
        # Check if best
        improved = False
        if accuracy > best_accuracy:
            if save_best_weights(test_weights, accuracy):
                improvement = (accuracy - best_accuracy) * 100
                print(f"[Worker {worker_id}] Iter {iteration}: {accuracy*100:.2f}% 🎉 +{improvement:.2f}pts ({elapsed:.1f}s)")
                best_accuracy = accuracy
                current_weights = test_weights
                improved = True
        
        if not improved and iteration % 10 == 0:
            print(f"[Worker {worker_id}] Iter {iteration}: {accuracy*100:.2f}% ({elapsed:.1f}s)")
        
        # Reload best weights periodically to benefit from other workers
        if iteration % 5 == 0:
            saved = load_best_weights()
            if saved and saved['accuracy'] > best_accuracy:
                current_weights = saved['weights']
                best_accuracy = saved['accuracy']

def main():
    num_workers = mp.cpu_count()
    iterations_per_worker = 1000  # Each worker will run 1000 iterations
    
    print("="*70)
    print("PARALLEL WEIGHT OPTIMIZATION")
    print("="*70)
    print(f"CPU Cores: {num_workers}")
    print(f"Iterations per worker: {iterations_per_worker}")
    print(f"Total iterations: {num_workers * iterations_per_worker}")
    print(f"Results: optimization_results.csv")
    print("="*70)
    
    # Check if events data exists
    events_file = 'events_data.json'
    if not os.path.exists(events_file):
        print(f"ERROR: {events_file} not found!")
        print("Run download-events.js first")
        return
    
    # Show resume status
    saved = load_best_weights()
    if saved:
        print(f"\nResuming from previous best: {saved['accuracy']*100:.2f}%")
    else:
        print("\nStarting fresh optimization")
    
    print("\nPress Ctrl+C to stop all workers\n")
    
    try:
        # Create worker processes
        processes = []
        for worker_id in range(num_workers):
            p = mp.Process(target=worker_process, args=(worker_id, iterations_per_worker))
            p.start()
            processes.append(p)
        
        # Wait for all workers
        for p in processes:
            p.join()
        
        print("\n" + "="*70)
        print("ALL WORKERS COMPLETED")
        print("="*70)
        
        # Show final best
        saved = load_best_weights()
        if saved:
            print(f"Final best accuracy: {saved['accuracy']*100:.2f}%")
        
    except KeyboardInterrupt:
        print("\n\nStopping all workers...")
        for p in processes:
            p.terminate()
            p.join()
        
        print("\n" + "="*70)
        print("OPTIMIZATION STOPPED")
        print("="*70)
        
        saved = load_best_weights()
        if saved:
            print(f"Best accuracy: {saved['accuracy']*100:.2f}%")

if __name__ == '__main__':
    # Required for Windows multiprocessing
    mp.freeze_support()
    main()
