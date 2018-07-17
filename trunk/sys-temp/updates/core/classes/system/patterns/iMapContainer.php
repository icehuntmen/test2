<?php
/**
 * Интерфейс контейнера типа ключ-значение
 *
 * У класса, реализуещего этот интерфейс, должны
 * существовать магические методы
 */
interface iMapContainer {
	/**
	 * Возвращает значение по ключу
	 * @param string $key ключ
	 * @return mixed
	 */
	public function get($key);
	/**
	 * Задано ли значение для ключа
	 * @param string $key ключ
	 * @return bool
	 */
	public function isExist($key);
	/**
	 * Записывает значение ключа и возвращает его
	 * @param string $key ключ
	 * @param mixed $value Значение
	 * @return mixed
	 */
	public function set($key, $value);
	/**
	 * Удаляет значение по ключу
	 * @param string $key ключ
	 * @return bool
	 */
	public function del($key);
	/**
	 * Возвращает содержимое контейнера
	 * @return array
	 */
	public function getArrayCopy();
	/** Очищает содержимое контейнера */
	public function clear();
	/** Синоним для get */
	public function __get($key);
	/** Синоним для isExist */
	public function __isset($key);
	/** Синоним для set */
	public function __set($key, $value);
	/** Синоним для del */
	public function __unset($key);
}