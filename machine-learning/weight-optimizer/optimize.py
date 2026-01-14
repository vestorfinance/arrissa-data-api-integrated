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
            weight = weights.get(event_id, 5.0)  # Default 5 if not in weights
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

def generate_training_data(events_file, weights):
    """Generate training CSV with given weights"""
    with open(events_file, 'r') as f:
        all_data = json.load(f)
    
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
            # Add or subtract 1
            change = np.random.choice([-1, 1])
            new_weights[event_id] = max(0, min(10, new_weights[event_id] + change))
    return new_weights

def main():
    print("="*70)
    print("WEIGHT OPTIMIZATION (NEXT WEEK STATE ONLY)")
    print("="*70)
    
    # Check if events data exists
    events_file = 'events_data.json'
    if not os.path.exists(events_file):
        print(f"ERROR: {events_file} not found!")
        print("Run download-events.js first")
        return
    
    print(f"Loading event data from: {events_file}")
    
    results_file = 'optimization_results.csv'
    best_weights_file = 'best_weights.json'
    
    # Try to resume from previous run
    if os.path.exists(best_weights_file):
        print(f"\nFound previous run: {best_weights_file}")
        with open(best_weights_file, 'r') as f:
            saved_data = json.load(f)
            best_weights = saved_data['weights']
            best_accuracy = saved_data['accuracy']
        
        # Count previous iterations
        if os.path.exists(results_file):
            prev_results = pd.read_csv(results_file)
            iteration = len(prev_results)
        else:
            iteration = 0
        
        print(f"Resuming from iteration {iteration}")
        print(f"Previous best accuracy: {best_accuracy*100:.2f}%")
    else:
        # Initialize weights (all start at 5)
        best_weights = {event_id: 5.0 for event_id in EVENT_CONFIG.keys()}
        best_accuracy = 0.0
        iteration = 0
        print("\nStarting fresh optimization")
    
    print(f"Optimizing {len(EVENT_CONFIG)} event weights")
    print(f"Weight range: 0-10 (increment: 1)")
    print(f"Results: {results_file}")
    print("\nPress Ctrl+C to stop\n")
    
    try:
        while True:
            iteration += 1
            start_time = time.time()
            
            print(f"\n{'='*70}")
            print(f"ITERATION {iteration}")
            print(f"{'='*70}")
            print(f"Best accuracy: {best_accuracy*100:.2f}%")
            
            # Generate new weights
            if iteration == 1:
                test_weights = best_weights
            else:
                test_weights = mutate_weights(best_weights)
            
            # Generate training data
            print("Generating training data with new weights...")
            df = generate_training_data(events_file, test_weights)
            
            # Train and evaluate
            print("Training model...")
            accuracy = train_and_evaluate(df)
            
            elapsed = time.time() - start_time
            print(f"\nResults:")
            print(f"  Accuracy: {accuracy*100:.2f}%")
            print(f"  Samples: {len(df)}")
            print(f"  Time: {elapsed:.1f}s")
            
            # Log results
            result_row = {
                'iteration': iteration,
                'timestamp': datetime.now().isoformat(),
                'accuracy': accuracy,
                'samples': len(df)
            }
            for event_id, weight in test_weights.items():
                result_row[f'w_{event_id}'] = weight
            
            result_df = pd.DataFrame([result_row])
            if not os.path.exists(results_file):
                result_df.to_csv(results_file, index=False)
            else:
                result_df.to_csv(results_file, mode='a', header=False, index=False)
            
            # Update best
            if accuracy > best_accuracy:
                improvement = (accuracy - best_accuracy) * 100
                print(f"\n🎉 NEW BEST! +{improvement:.2f} points")
                best_accuracy = accuracy
                best_weights = test_weights
                
                # Save best weights
                with open('best_weights.json', 'w') as f:
                    json.dump({
                        'accuracy': best_accuracy,
                        'weights': best_weights,
                        'timestamp': datetime.now().isoformat()
                    }, f, indent=2)
            
    except KeyboardInterrupt:
        print("\n\n" + "="*70)
        print("OPTIMIZATION STOPPED")
        print("="*70)
        print(f"Iterations: {iteration}")
        print(f"Best accuracy: {best_accuracy*100:.2f}%")
        print("="*70)

if __name__ == '__main__':
    main()
